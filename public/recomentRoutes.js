// Fungsi untuk mencari jarak terdekat dari titik keberangkatan yang ditentukan
function findClosestDestination(
    dataRoute,
    departureNumber,
    visitedDestinations
) {
    const departure = dataRoute.find(
        (d) => d.departureNumber === departureNumber
    );
    const unvisitedDestinations = departure.destinationResult.filter(
        (dest) => !visitedDestinations.includes(dest.destinationNumber)
    );

    // Menemukan tujuan terdekat
    let closest = null;
    let minDistance = Infinity;

    unvisitedDestinations.forEach((dest) => {
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

    while (visitedDestinations.length < dataRoute.length - 1) {
        // Ambil data sampai tujuan terakhir dihilangkan
        const closestDestination = findClosestDestination(
            dataRoute,
            currentDepartureNumber,
            visitedDestinations
        );
        if (closestDestination) {
            // Masukkan data keberangkatan dan tujuan ke dalam hasil
            route.push({
                departurePositions: dataRoute.find(
                    (d) => d.departureNumber === currentDepartureNumber
                ).departurePositions,
                departureNumber: currentDepartureNumber,
                destinationPositions: closestDestination.destinationPositions,
                destinationNumber: closestDestination.destinationNumber,
                distance: closestDestination.distance,
                duration: closestDestination.duration,
            });
            visitedDestinations.push(closestDestination.destinationNumber);
            currentDepartureNumber = closestDestination.destinationNumber; // Update keberangkatan selanjutnya
        }
    }

    return route;
}
