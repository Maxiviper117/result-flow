# JokeAPI Reference

## Overview

JokeAPI provides random jokes in multiple categories and formats. Use it to fetch jokes, filter by
category, or search for jokes containing specific keywords.

## Endpoints

### Random joke (any category)

`GET https://sv443.net/jokeapi/v2/joke/Any`

Example response:

```json
{
  "category": "Any",
  "type": "single",
  "joke": "Why couldn't the bicycle find its way home? It lost its bearings!",
  "flags": {
    "nsfw": false,
    "religious": false,
    "political": false,
    "racist": false,
    "sexist": false
  }
}
```

### Category joke

`GET https://sv443.net/jokeapi/v2/joke/Programming`

### Search jokes by keyword

`GET https://sv443.net/jokeapi/v2/joke/Search?contains=cat`

### Exclude flagged jokes

`GET https://sv443.net/jokeapi/v2/joke/Any?blacklistFlags=nsfw,religious,political,racist,sexist`