#!/bin/bash

grep 'Would download mp3:' crawl_europe1.out | sort -u | cut -d ' ' -f 4 > crawl_europe1.out.mp3links.sorted
grep 'Would download mp3:' crawl_podcast-onvasgener.out | sort -u | cut -d ' ' -f 4 > crawl_podcast-onvasgener.out.mp3links.sorted

colordiff crawl_podcast-onvasgener.out.mp3links.sorted crawl_europe1.out.mp3links.sorted

