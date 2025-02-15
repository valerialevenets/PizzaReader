<template>
    <div :class="reader.$route.name === 'Last Releases' ? 'row' : ''">
        <div :class="'py-sm-4' + (reader.$route.name === 'Last Releases' ? ' col-12 col-xl-8' : '')">
            <h2 class="text-center pt-sm-0 pt-2 pb-sm-2">{{ reader.__(reader.$route.name) }}</h2>
            <div v-if="reader.$route.name === 'All Comics'" id="all_comics_top_html" class="text-center"></div>
            <div class="row">
                <div v-for="comic in comics" :class="'col-lg-6 pb-1 pt-2 border-bottom item' + (comic.hidden ? ' hidden' : '')">
                    <div :class="'thumbnail float-start me-3 ms-1' + (reader.$route.name === 'Last Releases' ? ' small' : '')">
                        <router-link :to="comic.url"><img :src="comic.thumbnail_small" :onerror="'this.onerror=null;this.src=\'' + comic.thumbnail +'\';'" :alt="comic.title" :title="comic.title" class="rounded"></router-link>
                    </div>
                    <div class="text-truncate mb-1 d-flex flex-nowrap align-items-center">
                        <h5 class="fw-bold mb-0 me-1">
                            <a v-if="comic.id !== null" :href="reader.BASE_URL + 'admin/comics/' + comic.slug" target="_blank">
                                <span class="fas fa-edit fa-fw me-0" aria-hidden="true" title="Edit"></span>
                            </a>
                            <router-link class="text-wrap filter" :title="comic.title" :to="comic.url">
                                {{ comic.title }}
                            </router-link>
                        </h5>
                    </div>
                    <ul class="list-inline m-1">
                        <li v-if="reader.$route.name === 'Last Releases'" class="list-inline-item text-primary">
                            <span class="fas fa-star fa-fw" aria-hidden="true" title="Rating"></span>
                            <span v-if="comic.last_chapter != null && comic.last_chapter.rating != null" title="vote">{{ comic.last_chapter.rating }}</span>
                            <span v-else title="vote">-.--</span>
                        </li>
                        <li v-else class="list-inline-item text-primary">
                            <span class="fas fa-star fa-fw" aria-hidden="true" title="Rating"></span>
                            <span v-if="comic.rating != null" title="vote">{{ comic.rating }}</span>
                            <span v-else title="vote">-.--</span>
                        </li>
                        <li v-if="reader.$route.name === 'Last Releases'" class="list-inline-item text-info">
                            <span class="fas fa-eye fa-fw" aria-hidden="true" title="Views"></span>
                            <span>{{ comic.last_chapter ? comic.last_chapter.views : 0 }}</span>
                        </li>
                        <li v-else class="list-inline-item text-info">
                            <span class="fas fa-eye fa-fw" aria-hidden="true" title="Views"></span>
                            <span>{{ comic.views }}</span>
                        </li>
                        <li v-if="reader.$route.name !== 'Last Releases'" class="list-inline-item text-info">
                            <span style="margin-top: 2px;" class="badge badge-status p-1 text-white align-top" title="Status" :data-status="comic.status ? comic.status.toLowerCase() : ''">{{ comic.status }}</span>
                        </li>
                        <li v-if="comic.last_chapter != null" class="text-success">
                            <span class="fas fa-book-open fa-fw" aria-hidden="true" title="Last chapter"></span>
                            <router-link :to="comic.last_chapter.url" class="text-success fw-bold">{{ comic.last_chapter.full_title }}</router-link>
                        </li>
                    </ul>
                    <div v-if="reader.$route.path !== '/'" class="description text-pre pe-2 pe-lg-0">{{ comic.description }}</div>
                    <div v-else class="" :title="new Date(comic.last_chapter.published_on)">
                        <span title="Publication date" class="fa fa-clock fa-fw"></span> {{ comic.last_chapter.time }}
                    </div>
                </div>
            </div>
            <div v-if="reader.$route.name === 'All Comics'" id="all_comics_bottom_html" class="text-center"></div>
        </div>
        <div v-if="reader.$route.name === 'Last Releases'" class="mt-2 py-xl-4 mt-xl-0 col-12 col-xl-4">
            <template v-if="socials.length">
                <h2 class="text-center pb-sm-1">{{ reader.__('Socials') }}:</h2>
                <div class="socials text-center row">
                    <a v-for="social in socials" :href="social.url" class="col-3 mb-2" target="_blank">
                        <span :class="'fab fa-' + social.name.toLowerCase().split(' ', 1)[0] + ' fa-fw'" aria-hidden="true" :title="social.name"></span>
                    </a>
                </div>
            </template>
            <div id="homepage_html" class="text-center"></div>
        </div>
    </div>
</template>

<script>
import {mapGetters} from 'vuex';

export default {
    name: "Comics",
    mounted() {
        $('body').removeClass('body-reader hide-header');

        $('title').html(this.reader.SITE_NAME_FULL);
        $('meta[property="og:title"]').html(this.reader.SITE_NAME_FULL);
        this.$store.dispatch('fetchInfo', '/info');
        this.$store.dispatch('fetchComics', '/comics');
        this.comics.length && this.updateCustomHTML();
    },
    beforeRouteLeave(to, from, next) {
        if(from.path !== to.path) {
            switch(from.path) {
                case '/':
                    this.reader.clearCustomHTML('homepage_html');
                    break;
                case '/comics':
                    this.reader.clearCustomHTML('all_comics_top_html');
                    this.reader.clearCustomHTML('all_comics_bottom_html');
                    break;
            }
        }
        next();
    },
    updated() {
        if (this.reader.$route.path === '/comics') {
            $('#nav-search').hide();
            $('#nav-filter').show();
        } else {
            $('#nav-search').show();
            $('#nav-filter').hide();
        }
        this.comics.length && this.updateCustomHTML();
    },
    data() {
        return {
            reader: this.$root,
        }
    },
    methods: {
        updateCustomHTML() {
            if(this.reader.$route.name === 'Last Releases') {
                this.reader.updateCustomHTML('homepage_html');
            } else if (this.reader.$route.name === 'All Comics') {
                this.reader.updateCustomHTML('all_comics_top_html');
                this.reader.updateCustomHTML('all_comics_bottom_html');
            }
        },

    },
    computed: {
        ...mapGetters([
            'comics',
            'socials',
        ])
    }
}
</script>
