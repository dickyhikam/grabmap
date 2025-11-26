<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>GrabMaps</title>
    <meta name="description" content="GrabMaps adalah platform peta interaktif yang menyediakan informasi lokasi secara akurat. Jelajahi peta untuk menemukan tempat-tempat menarik." />
    <link rel="icon" href="images.png" type="image/x-icon" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <!-- MapLibre CSS -->
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.css" />

    <style>
        #map {
            width: 100%;
            height: calc(100% - 60px);
            /* 100% height minus the header height */
        }
    </style>
</head>

<body>

    <!-- Map Section -->
    <div id="map"></div>

    <!-- Bootstrap and MapLibre JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pzjw8f+ua7Kw1TIq0WjU7bNTwAOfUNwFO04TcK11PUkewTkhBf2LB5EZPKWk+73D" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.js"></script>
    <script>
        // Initialize map
        const map = new maplibregl.Map({
            container: 'map', // ID of the container element
            style: 'https://demotiles.maplibre.org/style.json', // Map style
            center: [106.8456, -6.2088], // Initial position [lng, lat]
            zoom: 12, // Initial zoom level
        });
    </script>
</body>

</html>