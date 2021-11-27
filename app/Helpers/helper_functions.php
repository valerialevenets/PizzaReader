<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DuplicatedChapter extends Exception {
}

const forbidden_words = ['update', 'edit', 'create', 'show', 'store', 'destroy'];

function generateSlug($class, $fields) {
    $fields['slug'] = isset($fields['slug']) ? Str::slug($fields['slug']) : (isset($fields['name']) ? Str::slug($fields['name']) : Str::slug($fields['title']));
    if (!$fields['slug']) $fields['slug'] = "oneshot";
    $fields['slug'] = substr($fields['slug'], 0, 50);
    $slug = is_numeric($fields['slug']) ? "c-" . $fields['slug'] : $fields['slug'];
    $i = 2;
    while (in_array($slug, forbidden_words) || (isset($fields['comic_id']) ? $class::slug($fields['comic_id'], $slug) : $class::slug($slug))) {
        $slug = $fields['slug'] . '-' . $i;
        $i++;
    }
    return $slug;
}

function getFormFieldsForValidation($fields) {
    $form_fields = [];
    foreach ($fields as $field) {
        $values = $field['values'];
        if (isset($field['parameters']['required']) && $field['parameters']['required']) {
            array_push($values, 'required');
        } else {
            array_push($values, 'nullable');
        }
        if (isset($field['parameters']['prohibited']) && $field['parameters']['prohibited']) {
            array_push($values, 'prohibited');
        }
        $form_fields[$field['parameters']['field']] = $values;
    }
    return $form_fields;
}

function getFieldsFromRequest($request, $form_fields) {
    $fields = [];
    foreach ($form_fields as $key => $value) {
        if ($request->$key != null) $fields[$key] = $request->$key;
        else $fields[$key] = null;
    }
    return $fields;
}

function trimChar($string, $char) {
    $c = $char[0];
    $string = preg_replace('/\s*' . $c . '\s*/', $c, $string);
    $string = preg_replace('/' . $c . '+' . $c . '+/', $c, $string);
    return trim($string, ' ' . $c);
}

function createZip($zip_absolute_path, $files) {
    if (empty($files)) return;
    $zip = new \ZipArchive();
    $zip->open($zip_absolute_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    foreach ($files as $file) {
        $zip->addFile($file['source'], $file['dest']);
    }
    try {
        $zip->close();
    } catch (\ErrorException $exception) {
        Log::error("createZip", ['download' => $zip_absolute_path, 'files' => $files]);
        Log::error($exception);
    }
}

function createPdf($pdf_absolute_path, $files) {
    // Sadly Intervention Image library doesn't support pdf...
    if (empty($files)) return;
    $pdf = new Imagick($files);
    $pdf->setImageFormat("pdf");
    try {
        $pdf->writeImages($pdf_absolute_path, true);
    } catch (\ErrorException $exception) {
        Log::error("createPdf", ['download' => $pdf_absolute_path, 'files' => $files]);
        Log::error($exception);
    }
}

function cleanDirectoryByExtension($path, $ext, $exclusions=[], $dry_run=false) {
    $files = Storage::files($path);
    foreach ($files as $key => $value) {
        if (!preg_match("/.$ext$/", $value) || in_array($value, $exclusions)) unset($files[$key]);
    }
    if (!$dry_run) Storage::delete($files);
    return $files;
}

function convertToTimezone($datetime, $timezone) {
    return Carbon::createFromFormat('Y-m-d H:i:s', $datetime, 'UTC')->tz($timezone);
}

function convertToUTC($datetime, $timezone){
    return Carbon::createFromFormat('Y-m-d\TH:i', $datetime, $timezone)->tz('UTC');
}

function getNameFromId($array, $id): string {
    $res = array_filter($array, function ($e) use (&$id) {return $e['id'] == $id;});
    if(empty($res)) return "N/A";
    else return array_pop($res)["name"];
}

function getSmallThumbnail($thumbnail) {
    return substr_replace($thumbnail, "-small.jpg", strrpos($thumbnail , '.', -1));
}
