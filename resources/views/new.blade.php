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
        // const data = [{
        //         "departurePositions": [106.79422, -6.598411],
        //         "departureNumber": 1,
        //         "destinationResult": [{
        //                 "destinationPositions": [106.806382, -6.582513],
        //                 "distance": 2.8,
        //                 "duration": 538,
        //                 "destinationNumber": 2
        //             },
        //             {
        //                 "destinationPositions": [106.821815, -6.584626],
        //                 "distance": 5.103,
        //                 "duration": 1019,
        //                 "destinationNumber": 3
        //             },
        //             {
        //                 "destinationPositions": [106.812567, -6.580683],
        //                 "distance": 3.693,
        //                 "duration": 743,
        //                 "destinationNumber": 4
        //             }
        //         ]
        //     },
        //     {
        //         "departurePositions": [106.806382, -6.582513],
        //         "departureNumber": 2,
        //         "destinationResult": [{
        //                 "destinationPositions": [106.79422, -6.598411],
        //                 "distance": 3.876,
        //                 "duration": 705,
        //                 "destinationNumber": 1
        //             },
        //             {
        //                 "destinationPositions": [106.821815, -6.584626],
        //                 "distance": 2.302,
        //                 "duration": 481,
        //                 "destinationNumber": 3
        //             },
        //             {
        //                 "destinationPositions": [106.812567, -6.580683],
        //                 "distance": 0.893,
        //                 "duration": 205,
        //                 "destinationNumber": 4
        //             }
        //         ]
        //     },
        //     {
        //         "departurePositions": [106.821815, -6.584626],
        //         "departureNumber": 3,
        //         "destinationResult": [{
        //                 "destinationPositions": [106.79422, -6.598411],
        //                 "distance": 5.839,
        //                 "duration": 1123,
        //                 "destinationNumber": 1
        //             },
        //             {
        //                 "destinationPositions": [106.806382, -6.582513],
        //                 "distance": 4.12,
        //                 "duration": 727,
        //                 "destinationNumber": 2
        //             },
        //             {
        //                 "destinationPositions": [106.812567, -6.580683],
        //                 "distance": 2.542,
        //                 "duration": 510,
        //                 "destinationNumber": 4
        //             }
        //         ]
        //     },
        //     {
        //         "departurePositions": [106.812567, -6.580683],
        //         "departureNumber": 4,
        //         "destinationResult": [{
        //                 "destinationPositions": [106.79422, -6.598411],
        //                 "distance": 4.848,
        //                 "duration": 940,
        //                 "destinationNumber": 1
        //             },
        //             {
        //                 "destinationPositions": [106.806382, -6.582513],
        //                 "distance": 1.872,
        //                 "duration": 349,
        //                 "destinationNumber": 2
        //             },
        //             {
        //                 "destinationPositions": [106.821815, -6.584626],
        //                 "distance": 1.522,
        //                 "duration": 363,
        //                 "destinationNumber": 3
        //             }
        //         ]
        //     }
        // ];

        // // Fungsi untuk mencari jarak terdekat dari titik keberangkatan yang ditentukan
        // function findClosestDestination(departureNumber, visitedDestinations) {
        //     const departure = data.find(d => d.departureNumber === departureNumber);
        //     const unvisitedDestinations = departure.destinationResult.filter(dest => !visitedDestinations.includes(dest.destinationNumber));

        //     // Menemukan tujuan terdekat
        //     let closest = null;
        //     let minDistance = Infinity;

        //     unvisitedDestinations.forEach(dest => {
        //         if (dest.distance < minDistance) {
        //             closest = dest;
        //             minDistance = dest.distance;
        //         }
        //     });

        //     return closest;
        // }

        // // Fungsi untuk menentukan urutan perjalanan berdasarkan jarak terdekat, tanpa tujuan terakhir
        // function findRoute(data) {
        //     const route = [];
        //     let visitedDestinations = [];
        //     let currentDepartureNumber = 1; // Mulai dari keberangkatan 1

        //     while (visitedDestinations.length < data.length - 1) { // Ambil data sampai tujuan terakhir dihilangkan
        //         const closestDestination = findClosestDestination(currentDepartureNumber, visitedDestinations);
        //         if (closestDestination) {
        //             // Masukkan data keberangkatan dan tujuan ke dalam hasil
        //             route.push({
        //                 departurePositions: data.find(d => d.departureNumber === currentDepartureNumber).departurePositions,
        //                 departureNumber: currentDepartureNumber,
        //                 destinationPositions: closestDestination.destinationPositions,
        //                 destinationNumber: closestDestination.destinationNumber,
        //                 distance: closestDestination.distance,
        //                 duration: closestDestination.duration
        //             });
        //             visitedDestinations.push(closestDestination.destinationNumber);
        //             currentDepartureNumber = closestDestination.destinationNumber; // Update keberangkatan selanjutnya
        //         }
        //     }

        //     return route;
        // }

        // const route = findRoute();
        // console.log("Urutan perjalanan:", route);
    </script>

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
            // Fungsi untuk menyimpan dan mengelompokkan data berdasarkan Departure Coordinates
            const routeDataArray = storeRouteData(dataDesti);
            createTableFromRouteData(routeDataArray);
        });

        // Fungsi untuk menampilkan data dan memasukkan ke dalam array berdasarkan Departure Coordinates
        function storeRouteData(data) {
            const routeMatrix = data[0].RouteMatrix;
            const departurePositions = data[0].SnappedDeparturePositions;
            const destinationPositions = data[0].SnappedDestinationPositions;

            let routeDataArray = []; // Array untuk menyimpan data berdasarkan Departure Coordinates

            // Iterasi melalui seluruh RouteMatrix dan masukkan ke dalam array
            for (let i = 0; i < routeMatrix.length; i++) {
                for (let j = 0; j < routeMatrix[i].length; j++) {
                    const routeData = routeMatrix[i][j];
                    const distance = routeData.Distance;
                    const duration = routeData.DurationSeconds;

                    const departureCoords = departurePositions[i];
                    const destinationCoords = destinationPositions[j];

                    // Cek jika jaraknya lebih besar dari 0
                    if (distance > 0) {
                        // const formattedDuration = formatDuration(duration);
                        // const formattedDistance = formatDistance(distance);

                        // Cari apakah Departure Coordinates sudah ada dalam array
                        const existingEntry = routeDataArray.find(entry =>
                            JSON.stringify(entry.departurePositions) === JSON.stringify(departureCoords)
                        );

                        // Jika ditemukan, tambahkan data ke dalam array yang sudah ada
                        if (existingEntry) {
                            existingEntry.destinationResult.push({
                                destinationPositions: destinationCoords,
                                distance: distance,
                                duration: duration,
                                destinationNumber: j + 1 // Menambahkan nomor untuk Destination
                            });
                        } else {
                            // Jika belum ada, buat entry baru untuk Departure Coordinates tersebut
                            routeDataArray.push({
                                departurePositions: departureCoords,
                                departureNumber: i + 1, // Menambahkan nomor untuk Departure
                                destinationResult: [{
                                    destinationPositions: destinationCoords,
                                    distance: distance,
                                    duration: duration,
                                    destinationNumber: j + 1 // Menambahkan nomor untuk Destination
                                }]
                            });
                        }
                    }
                }
            }

            // Tampilkan array yang sudah terisi
            // console.log(findRoute(routeDataArray));
            return findRoute(routeDataArray);
        }

        // Fungsi untuk mencari jarak terdekat dari titik keberangkatan yang ditentukan
        function findClosestDestination(dataRoute, departureNumber, visitedDestinations) {
            const departure = dataRoute.find(d => d.departureNumber === departureNumber);
            const unvisitedDestinations = departure.destinationResult.filter(dest => !visitedDestinations.includes(dest.destinationNumber));

            // Menemukan tujuan terdekat
            let closest = null;
            let minDistance = Infinity;

            unvisitedDestinations.forEach(dest => {
                if (dest.distance < minDistance) {
                    closest = dest;
                    minDistance = dest.distance;
                }
            });

            return closest;
        }

        // Fungsi untuk menentukan urutan perjalanan berdasarkan jarak terdekat, tanpa tujuan terakhir
        function findRoute(dataRoute) {
            const route = [];
            let visitedDestinations = [];
            let currentDepartureNumber = 1; // Mulai dari keberangkatan 1

            while (visitedDestinations.length < dataRoute.length - 1) { // Ambil data sampai tujuan terakhir dihilangkan
                const closestDestination = findClosestDestination(dataRoute, currentDepartureNumber, visitedDestinations);
                if (closestDestination) {
                    // Masukkan data keberangkatan dan tujuan ke dalam hasil
                    route.push({
                        departurePositions: dataRoute.find(d => d.departureNumber === currentDepartureNumber).departurePositions,
                        departureNumber: currentDepartureNumber,
                        destinationPositions: closestDestination.destinationPositions,
                        destinationNumber: closestDestination.destinationNumber,
                        distance: closestDestination.distance,
                        duration: closestDestination.duration
                    });
                    visitedDestinations.push(closestDestination.destinationNumber);
                    currentDepartureNumber = closestDestination.destinationNumber; // Update keberangkatan selanjutnya
                }
            }

            return route;
        }

        // Fungsi untuk membuat tabel berdasarkan routeDataArray
        function createTableFromRouteData(routeDataArray) {
            const tableBody = document.querySelector("#route-table tbody");
            tableBody.innerHTML = ''; // Kosongkan tabel sebelumnya

            // Iterasi melalui routeDataArray dan buat baris untuk setiap Departure Coordinates
            routeDataArray.forEach(entry => {
                const departureCoords = entry.departurePositions;

                const destinationCoords = entry.destinationPositions;
                const distance = formatDistance(entry.distance);
                const duration = formatDuration(entry.duration);

                // Membuat baris baru untuk tabel
                const row = document.createElement("tr");

                // Tambahkan kolom Departure Coordinates dengan nomor
                const departureCell = document.createElement("td");
                departureCell.textContent = `Departure ${entry.departureNumber}: ${departureCoords[0]}, ${departureCoords[1]}`;
                row.appendChild(departureCell);

                // Tambahkan kolom Destination Coordinates dengan nomor
                const destinationCell = document.createElement("td");
                destinationCell.textContent = `Destination ${entry.destinationNumber}: ${destinationCoords[0]}, ${destinationCoords[1]}`;
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