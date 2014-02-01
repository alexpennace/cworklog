<VirtualHost *:80>
        ServerAdmin webmaster@cworklog.com
        ServerName  cworklog.com
        ServerAlias www.cworklog.com

        # Indexes + Directory Root.
        DocumentRoot /home/cworklog/public_html
        DirectoryIndex index.php

        <IfModule mod_php5.c>
                AddType application/x-httpd-php .php

                php_flag magic_quotes_gpc Off
                php_flag track_vars On
                php_flag register_globals Off
                php_value include_path .
        </IfModule>

        # Logfiles
        ErrorLog  /home/cworklog/logs/error.log
        CustomLog /home/cworklog/logs/access.log combined
</VirtualHost>

<IfModule mod_ssl.c>
<VirtualHost 198.58.105.195:443>
	ServerAdmin webmaster@cworklog.com
	SSLEngine On
        SSLCertificateFile /etc/ssl/certs/cworklog.com.crt
	SSLCertificateKeyFile /etc/ssl/certs/cworklog.key
	SSLCACertificateFile /etc/ssl/certs/sf_bundle.crt
 
       # Indexes + Directory Root.
        DocumentRoot /home/cworklog/public_html
        DirectoryIndex index.php

        <IfModule mod_php5.c>
                AddType application/x-httpd-php .php

                php_flag magic_quotes_gpc Off
                php_flag track_vars On
                php_flag register_globals Off
                php_value include_path .
        </IfModule>

        # Logfiles
        ErrorLog  /home/cworklog/logs/error.log
        CustomLog /home/cworklog/logs/access.log combined


	BrowserMatch "MSIE [2-6]" \
		nokeepalive ssl-unclean-shutdown \
		downgrade-1.0 force-response-1.0
	# MSIE 7 and newer should be able to use keepalive
	BrowserMatch "MSIE [17-9]" ssl-unclean-shutdown

</VirtualHost>
</IfModule>
