@extends('layouts.admin')
@section('content')
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-12">
                    <h3 class="mt-1 float-start">Create profile</h3>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('POST')

                <div class="mb-3 row">
                    <label for="name" class="form-label col-sm-2 col-form-label required">Name</label>
                    <div class="col-sm-10">
                        <input type="text" maxlength="191" name="name" id="name" placeholder="Name"
                               class="form-control @error('name') is-invalid @enderror col-sm-12"
                               value="" required>
                        @error('name')
                        @include('partials.invalid_feedback')
                        @enderror
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="email" class="form-label col-sm-2 col-form-label required">Email</label>
                    <div class="col-sm-10">
                        <input type="email" maxlength="191" name="email" id="email" placeholder="Email"
                               class="form-control @error('email') is-invalid @enderror col-sm-12"
                               value="" required>
                        @error('email')
                        @include('partials.invalid_feedback')
                        @enderror
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="password" class="form-label col-sm-2 col-form-label">Password</label>
                    <div class="col-sm-10 d-flex">
                        <div class="input-group">
                            <input type="password" minlength="8" maxlength="191" name="password" id="password"
                               placeholder="Password" class="form-control @error('password') is-invalid @enderror col-sm-12"
                               value="">
                            <div class="input-group-text"><span id="toggle-password" class="fas fa-eye fa-fw"></span>
                            </div>
                            <div class="input-group-text"><span id="generate-password" class="fas fa-magic fa-fw"></span>
                            </div>
                        </div>
                        @error('password')
                        @include('partials.invalid_feedback')
                        @enderror
                    </div>
                </div>

                <div class="d-grid gap-2 max-auto">
                    <button type="submit" id="submit" class="btn btn-lg btn-success">Create</button>
                </div>
            </form>
        </div>
    </div>
@endsection
