-I made the assumption only GET requests are needed, as the API is only supposed to serve vessel tracks.
-I also made the assumption that the support for the following types has to do with the format in which the data is served/output back to the client after a GET request.
-The SQL queries aren't safe and are vulnerable to attacks (It hasn't been asked to secure them, so I just used the GET parameters unfiltered and uncleared, copying them directly to construct the query)
-The SQL database populated and used is included in this folder (vtapi_db.sql)

.. -- Examples of requests (GET requests) -- .. 
localhost/vtapi/index.php?mmsi=311040700, 311486000&minLat=33&maxLat=35&minLon=17
localhost/vtapi/index.php?mmsi=311040700, 311486000&maxLat=40&timeInterval=1372700220, 1372700460&minLon=17
localhost/vtapi/index.php?mmsi=311486000&maxLat=40