Install software
=

You should install Sphinx Search. You should also install a stemer if you wish to.

E.g. under Ubuntu (12.04) :
sudo apt-get install sphinxsearch python-stemmer

Configure Sphinx Search
=

You should configure Sphinx Search according to your needs (Ubuntu : /etc/sphinxsearch/sphinx.conf).
Please read [Sphinx doc](http://sphinxsearch.com/docs/current.html) for a complete overview.
Here is a sample config (part of config for Jelix docs) :

    source docs-jelix-1-0-fr
    {
    	type			= xmlpipe
    	xmlpipe_command		= sudo -u www-data php /path/to/app/doc_fr/scripts/manage.php gtwsphinx~sphinxSource:sphinxSearchExport manuel-1.0 index.gtw
    }
    source docs-jelix-1-0-en : docs-jelix-1-0-fr
    {
    	xmlpipe_command		= sudo -u www-data php /path/to/app/doc_en/scripts/manage.php gtwsphinx~sphinxSource:sphinxSearchExport manual-1.0 index.gtw
    }
    
    index docs-jelix-1-0-fr
    {
    	source			= docs-jelix-1-0-fr
    	path			= /var/lib/sphinxsearch/data/docs-jelix-1-0
    	docinfo			= extern
    	mlock			= 0
    	morphology		= libstemmer_french
    	min_word_len		= 1
    	charset_type		= utf-8
    	charset_table = 0..9, A..Z->a..z, _, a..z, U+410..U+42F->U+430..U+44F, U+430..U+44F,U+23, U+C0..U+DD->U+E0..U+FD, U+E0..U+FD, U+DF, U+00E9->e
    	html_strip		= 1
    	html_index_attrs	= img=alt,title; a=title;
    	html_remove_elements	= style, script
    }
    
    index docs-jelix-1-0-en : docs-jelix-1-0-fr
    {
    	source			= docs-jelix-1-0-en
    	path			= /var/lib/sphinxsearch/data/docs-jelix-1-0-en
    	morphology		= stem_en
    }
    
    indexer
    {
    	mem_limit		= 32M
    }
    
    searchd
    {
    	listen			= 9312
    	listen			= 9306:mysql41
    	log			= /var/log/sphinxsearch/searchd.log
    	query_log		= /var/log/sphinxsearch/query.log
    	read_timeout		= 5
    	client_timeout		= 300
    	max_children		= 30
    	pid_file		= /var/run/searchd.pid
    	max_matches		= 1000
    	seamless_rotate		= 1
    	preopen_indexes		= 1
    	unlink_old		= 1
    	mva_updates_pool	= 1M
    	max_packet_size		= 8M
    	max_filters		= 256
    	max_filter_values	= 4096
    	max_batch_queries	= 32
    	workers			= prefork
    }

Indexing
=

You should start indexing with :
    sudo indexer --all --rotate

Running Sphinx Search
=
Sphinx Search deamon must be started with :
    sudo searchd

Please note that it should start automatically on system startup.
For e.g. Ubuntu, one easy way to do it is to edit /etc/rc/local and add this line :
    /usr/bin/searchd
before the line that states :
    exit 0

sphinxsearch module usage
=
This module is a helper to use Sphinx Search with Jelix.
Please look at how [gitiwiki](https://github.com/laurentj/gitiwiki)'s gtwsphinx module uses it (basically, you need an event listener for indexing and an action to answer searches).
Your app should have a "sphinxsearch" jCache profile.

