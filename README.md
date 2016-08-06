# Amoscato Server [![Build Status](https://travis-ci.org/namoscato/amoscato-server.svg?branch=master)](https://travis-ci.org/namoscato/amoscato-server)

Server-side processes behind [amoscato.com](http://amoscato.com/) built with [Symfony](https://symfony.com/).

## Console Commands

### `amoscato:stream:load`

Loads stream data from the specified set of sources. If no sources are specified, data from all sources will be loaded.

Available sources include:

* `flickr` - latest photos from [Flickr](https://www.flickr.com/)
* `foodspotting` - latest reviews from [Foodspotting](http://www.foodspotting.com/)
* `github` - latest public commits from [GitHub](https://github.com/)
* `goodreads` - latest read books from [Goodreads](https://www.goodreads.com/)
* `lastfm` - latest scrobbles from [Last.fm](http://www.last.fm/)
* `vimeo` - latest favorites from [Vimeo](https://vimeo.com/)
* `youtube` - latest favorites from [YouTube](https://www.youtube.com/)

