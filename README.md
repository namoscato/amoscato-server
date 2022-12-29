[![ci](https://github.com/namoscato/amoscato-server/actions/workflows/ci.yml/badge.svg)](https://github.com/namoscato/amoscato-server/actions/workflows/ci.yml)

# Amoscato Server

Server-side processes behind [amoscato.com](https://amoscato.com/) built with [Symfony](https://symfony.com/).

## Console Commands

### `amoscato:current:load`

Loads and caches current data from a set of sources:

-   `athleticActivity` - latest athletic activity from [Strava](https://www.strava.com/)
-   `book` - currently reading book from [Goodreads](https://www.goodreads.com/)
-   `drink` - latest checkin from [Untappd](https://untappd.com/)
-   `music` - latest scrobble from [Last.fm](http://www.last.fm/)
-   `video` - latest favorite or like from [YouTube](https://www.youtube.com/) or [Vimeo](https://vimeo.com/) respectively

### `amoscato:stream:cache [--size=1000]`

Stores the cached result of _size_ stream items to S3.

### `amoscato:stream:load [source ...]`

Loads stream data from the specified set of sources. If no sources are specified, data from all sources will be loaded.

Available sources include:

-   `flickr` - latest photos from [Flickr](https://www.flickr.com/)
-   `github` - latest public commits from [GitHub](https://github.com/)
-   `goodreads` - latest read books from [Goodreads](https://www.goodreads.com/)
-   `lastfm` - latest scrobbles from [Last.fm](http://www.last.fm/)
-   `twitter` - latest tweets from [Twitter](https://twitter.com/)
-   `untappd` - latest badges from [Untappd](https://untappd.com/)
-   `vimeo` - latest favorites from [Vimeo](https://vimeo.com/)
-   `youtube` - latest favorites from [YouTube](https://www.youtube.com/)

### `amoscato:stream:truncate [--size=1500]`

Truncates historic stream data, keeping a stream of the specified _size_.
