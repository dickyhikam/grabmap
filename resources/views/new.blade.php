<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Demo MAP Grab (AWS)</title>
    <meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no" />

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .route-info {
            margin-top: 20px;
            font-size: 18px;
            color: #333;
            font-weight: bold;
        }

        .btn-refresh {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            font-size: 16px;
        }

        .btn-refresh:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <h1>Route Matrix Data</h1>
    <p class="route-info">Data Jarak dan Durasi Perjalanan antara Titik Keberangkatan dan Tujuan</p>

    <!-- Tabel untuk menampilkan hasil -->
    <table id="route-table">
        <thead>
            <tr>
                <th>Departure Coordinates</th>
                <th>Destination Coordinates</th>
                <th>Distance (km)</th>
                <th>Duration (seconds)</th>
            </tr>
        </thead>
        <tbody>
            <!-- Data akan dimasukkan di sini oleh JavaScript -->
        </tbody>
    </table>

    <button class="btn-refresh" onclick="displayRouteData(dataDesti)">Tampilkan Data</button>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let dataDesti = [{
            "RouteMatrix": [
                [{
                        "Distance": 0,
                        "DurationSeconds": 0
                    },
                    {
                        "Distance": 2.8,
                        "DurationSeconds": 538
                    },
                    {
                        "Distance": 5.103,
                        "DurationSeconds": 1019
                    },
                    {
                        "Distance": 3.693,
                        "DurationSeconds": 743
                    }
                ],
                [{
                        "Distance": 3.876,
                        "DurationSeconds": 705
                    },
                    {
                        "Distance": 0,
                        "DurationSeconds": 0
                    },
                    {
                        "Distance": 2.302,
                        "DurationSeconds": 481
                    },
                    {
                        "Distance": 0.893,
                        "DurationSeconds": 205
                    }
                ],
                [{
                        "Distance": 5.839,
                        "DurationSeconds": 1123
                    },
                    {
                        "Distance": 4.12,
                        "DurationSeconds": 727
                    },
                    {
                        "Distance": 0,
                        "DurationSeconds": 0
                    },
                    {
                        "Distance": 2.542,
                        "DurationSeconds": 510
                    }
                ],
                [{
                        "Distance": 4.848,
                        "DurationSeconds": 940
                    },
                    {
                        "Distance": 1.872,
                        "DurationSeconds": 349
                    },
                    {
                        "Distance": 1.522,
                        "DurationSeconds": 363
                    },
                    {
                        "Distance": 0,
                        "DurationSeconds": 0
                    }
                ]
            ],
            "SnappedDeparturePositions": [
                [
                    106.79422,
                    -6.598411
                ],
                [
                    106.806382,
                    -6.582513
                ],
                [
                    106.821815,
                    -6.584626
                ],
                [
                    106.812567,
                    -6.580683
                ]
            ],
            "SnappedDestinationPositions": [
                [
                    106.79422,
                    -6.598411
                ],
                [
                    106.806382,
                    -6.582513
                ],
                [
                    106.821815,
                    -6.584626
                ],
                [
                    106.812567,
                    -6.580683
                ]
            ],
            "Summary": {
                "DataSource": "Grab",
                "RouteCount": 16,
                "ErrorCount": 0,
                "DistanceUnit": "Kilometers"
            }
        }];

        // ====== INISIALISASI PETA ======
        $(document).ready(function() {
            // displayRouteData(dataDesti);

            // Fungsi untuk menyimpan dan mengelompokkan data berdasarkan Departure Coordinates
            const routeDataArray = storeRouteData(dataDesti);

            // Fungsi untuk memilih rute dengan jarak terdekat dari setiap Departure Coordinates
            // const closestRouteData = selectClosestRoute(routeDataArray);

            createTableFromRouteData(routeDataArray);

            // Tampilkan data setelah memilih rute terdekat
            // console.log(closestRouteData);

        });

        // Fungsi untuk menampilkan data ke dalam tabel
        function displayRouteData(data) {
            const routeMatrix = data[0].RouteMatrix;
            const departurePositions = data[0].SnappedDeparturePositions;
            const destinationPositions = data[0].SnappedDestinationPositions;

            const tableBody = document.querySelector("#route-table tbody");
            tableBody.innerHTML = ''; // Kosongkan tabel sebelumnya

            // Iterasi melalui seluruh RouteMatrix dan masukkan ke dalam tabel
            for (let i = 0; i < routeMatrix.length; i++) {
                for (let j = 0; j < routeMatrix[i].length; j++) {
                    const routeData = routeMatrix[i][j];
                    const distance = routeData.Distance;
                    const duration = routeData.DurationSeconds;

                    // Cek jika jaraknya lebih besar dari 0
                    if (distance > 0) {
                        const departureCoords = departurePositions[i];
                        const destinationCoords = destinationPositions[j];

                        // Format durasi dan jarak
                        const formattedDuration = formatDuration(duration);
                        const formattedDistance = formatDistance(distance);

                        // Tambahkan baris baru ke dalam tabel
                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td>${departureCoords[0]}, ${departureCoords[1]}</td>
                            <td>${destinationCoords[0]}, ${destinationCoords[1]}</td>
                            <td>${formattedDistance}</td>
                            <td>${formattedDuration}</td>
                        `;
                        tableBody.appendChild(row);
                    }
                }
            }
        }

        // Fungsi untuk menampilkan data dan memasukkan ke dalam array berdasarkan Departure Coordinates
        // function storeRouteData(data) {
        //     const routeMatrix = data[0].RouteMatrix;
        //     const departurePositions = data[0].SnappedDeparturePositions;
        //     const destinationPositions = data[0].SnappedDestinationPositions;

        //     let routeDataArray = []; // Array untuk menyimpan data berdasarkan Departure Coordinates

        //     // Iterasi melalui seluruh RouteMatrix dan masukkan ke dalam array
        //     for (let i = 0; i < routeMatrix.length; i++) {
        //         for (let j = 0; j < routeMatrix[i].length; j++) {
        //             const routeData = routeMatrix[i][j];
        //             const distance = routeData.Distance;
        //             const duration = routeData.DurationSeconds;

        //             const departureCoords = departurePositions[i];
        //             const destinationCoords = destinationPositions[j];

        //             // Cek jika jaraknya lebih besar dari 0
        //             if (distance > 0) {
        //                 // const formattedDuration = formatDuration(duration);
        //                 // const formattedDistance = formatDistance(distance);

        //                 // Cari apakah Departure Coordinates sudah ada dalam array
        //                 const existingEntry = routeDataArray.find(entry =>
        //                     JSON.stringify(entry.departurePositions) === JSON.stringify(departureCoords)
        //                 );

        //                 // Jika ditemukan, tambahkan data ke dalam array yang sudah ada
        //                 if (existingEntry) {
        //                     existingEntry.destinationResult.push({
        //                         destinationPositions: destinationCoords,
        //                         distance: distance,
        //                         duration: duration,
        //                         destinationNumber: j + 1 // Menambahkan nomor untuk Destination
        //                     });
        //                 } else {
        //                     // Jika belum ada, buat entry baru untuk Departure Coordinates tersebut
        //                     routeDataArray.push({
        //                         departurePositions: departureCoords,
        //                         departureNumber: i + 1, // Menambahkan nomor untuk Departure
        //                         destinationResult: [{
        //                             destinationPositions: destinationCoords,
        //                             distance: distance,
        //                             duration: duration,
        //                             destinationNumber: j + 1 // Menambahkan nomor untuk Destination
        //                         }]
        //                     });
        //                 }
        //             }
        //         }
        //     }

        //     // Tampilkan array yang sudah terisi
        //     console.log(routeDataArray);
        //     return routeDataArray;
        // }

        // Fungsi untuk memilih rute terdekat untuk setiap Departure Coordinates
        function selectClosestRoute(routeDataArray) {
            // Iterasi melalui routeDataArray untuk memilih rute terdekat
            routeDataArray.forEach(entry => {
                // Mengurutkan destinationResult berdasarkan distance (jarak terdekat)
                entry.destinationResult.sort((a, b) => a.distance - b.distance);

                // Ambil hanya rute pertama (terdekat)
                const closestRoute = entry.destinationResult[0];

                // Simpan hanya rute terdekat
                entry.destinationResult = [closestRoute];
            });

            // Tampilkan array yang sudah terisi
            console.log(routeDataArray);
            return routeDataArray;
        }

        // Fungsi untuk membuat tabel berdasarkan routeDataArray
        function createTableFromRouteData(routeDataArray) {
            const tableBody = document.querySelector("#route-table tbody");
            tableBody.innerHTML = ''; // Kosongkan tabel sebelumnya

            // Iterasi melalui routeDataArray dan buat baris untuk setiap Departure Coordinates
            routeDataArray.forEach(entry => {
                const departureCoords = entry.departurePositions;

                // Untuk setiap Departure Coordinates, iterasi melalui routes
                entry.destinationResult.forEach(route => {
                    const destinationCoords = route.destinationPositions;
                    const distance = route.distance;
                    const duration = route.duration;

                    // Membuat baris baru untuk tabel
                    const row = document.createElement("tr");

                    // Tambahkan kolom Departure Coordinates dengan nomor
                    const departureCell = document.createElement("td");
                    departureCell.textContent = `Departure ${entry.departureNumber}: ${departureCoords[0]}, ${departureCoords[1]}`;
                    row.appendChild(departureCell);

                    // Tambahkan kolom Destination Coordinates dengan nomor
                    const destinationCell = document.createElement("td");
                    destinationCell.textContent = `Destination ${route.destinationNumber}: ${destinationCoords[0]}, ${destinationCoords[1]}`;
                    row.appendChild(destinationCell);

                    // Tambahkan kolom Distance
                    const distanceCell = document.createElement("td");
                    distanceCell.textContent = distance;
                    row.appendChild(distanceCell);

                    // Tambahkan kolom Duration
                    const durationCell = document.createElement("td");
                    durationCell.textContent = duration;
                    row.appendChild(durationCell);

                    // Tambahkan baris ke dalam body tabel
                    tableBody.appendChild(row);
                });
            });
        }

        // Fungsi untuk mengonversi durasi dalam detik ke format jam dan menit
        function formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600); // Menghitung jam
            const minutes = Math.floor((seconds % 3600) / 60); // Menghitung menit
            const remainingSeconds = seconds % 60; // Menghitung detik yang tersisa

            // Menyusun format waktu
            if (hours > 0) {
                return `${hours} jam ${minutes} menit`; // Jika ada jam
            } else if (minutes > 0) {
                return `${minutes} menit`; // Jika hanya menit
            } else {
                return `${remainingSeconds} detik`; // Jika hanya detik
            }
        }

        // Fungsi untuk mengonversi jarak dalam meter ke format kilometer atau meter
        function formatDistance(distance) {
            if (distance >= 1) {
                // Jika jarak lebih dari atau sama dengan 1 km, tampilkan dalam km
                return `${distance.toFixed(2)} km`;
            } else {
                // Jika jarak kurang dari 1 km, tampilkan dalam meter
                return `${(distance * 1000).toFixed(0)} m`; // Konversi ke meter
            }
        }
    </script>
</body>

</html>