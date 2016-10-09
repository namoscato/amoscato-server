# Amoscato Server [![Build Status](https://travis-ci.org/namoscato/amoscato-server.svg?branch=master)](https://travis-ci.org/namoscato/amoscato-server)

Server-side processes behind [amoscato.com](http://amoscato.com/) built with [Symfony](https://symfony.com/).

## Console Commands

### `amoscato:current:load`

Loads and caches current data from a set of sources:

* `book` - currently reading book from [Goodreads](https://www.goodreads.com/)
* `music` - latest scrobble from [Last.fm](http://www.last.fm/)
* `video` - latest favorite or like from [YouTube](https://www.youtube.com/) or [Vimeo](https://vimeo.com/) respectively

### `amoscato:stream:cache [--size=1000]`

Stores the cached result of _size_ stream items to an external server via FTP.

### `amoscato:stream:load [source ...]`

Loads stream data from the specified set of sources. If no sources are specified, data from all sources will be loaded.

Available sources include:

* `flickr` - latest photos from [Flickr](https://www.flickr.com/)
* `foodspotting` - latest reviews from [Foodspotting](http://www.foodspotting.com/)
* `github` - latest public commits from [GitHub](https://github.com/)
* `goodreads` - latest read books from [Goodreads](https://www.goodreads.com/)
* `lastfm` - latest scrobbles from [Last.fm](http://www.last.fm/)
* `twitter` - latest tweets from [Twitter](https://twitter.com/)
* `vimeo` - latest favorites from [Vimeo](https://vimeo.com/)
* `youtube` - latest favorites from [YouTube](https://www.youtube.com/)

