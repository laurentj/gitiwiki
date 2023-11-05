;<?php die(''); ?>
;for security reasons, don't remove or modify the first line

[gtwrepo]
current-manual=mymanual

[gtwrepo:default]
path = /srv/gitiwiki/tests/data/repositories/default/
generators = gitiwikiGenerators
branch = master
title= "Test repository"
sphinxIndex= "gitiwikiTest"
order=2

[gtwrepo:defaultwithbasepath]
path="/srv/gitiwiki/tests/data/repositories/default/"
generators=gitiwikiGenerators
branch=master
title="Test repository 2"
basepath=/rootmanual/
order=3

[gtwrepo:mymanual]
path = /srv/gitiwiki/tests/data/repositories/default/
generators = gitiwikiGenerators
branch = master
title= "Test repository"
sphinxIndex= "gitiwikiTest"
robotsNoIndex=off
urlName=current-manual
order=1

[jcache]


[jcache:sphinxsearch]
;    disable or enable cache for this profile
enabled = 1
;    driver type (file, db, memcached)
driver = file
;    TTL used (0 means no expire)
ttl = 0

; Automatic cleaning configuration (not necessary with memcached)
;   0 means disabled
;   1 means systematic cache cleaning of expired data (at each set or add call)
;   greater values mean less frequent cleaning
;automatic_cleaning_factor = 0

; Parameters for file driver :

; directory where to put the cache files (optional default jApp::tempPath('cache/'))
cache_dir = temp:cache
; enable / disable locking file
file_locking = 1
; directory level. Set the directory structure level. 0 means "no directory structure", 1 means "one level of directory", 2 means "two levels"...
directory_level = 0
; umask for directory structure (default '0700')
directory_umask =
; prefix for cache files (default 'jelix_cache')
file_name_prefix = ""
; umask for cache files (default '0600')
cache_file_umask =

