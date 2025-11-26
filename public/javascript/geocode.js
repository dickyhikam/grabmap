// ====== REVERSE GEOCODING (koordinat -> alamat label) ======
async function reverseGeocode(lon, lat) {
    const url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${mapPlace}/search/position?key=${apiKey}`;
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                Position: [lon, lat],
            }), // ⚠️ urutan [lon, lat]
        });

        if (response.ok) {
            const data = await response.json();
            const results = data["Results"];
            if (results && results.length > 0) {
                const label = results[0]?.Place?.Label;
                return label ?? "Alamat tidak ditemukan";
            }
        }
        return "Alamat tidak ditemukan";
    } catch (error) {
        return "Galat geocoding";
    }
}

// ====== SEARCH GEOCODING (teks -> daftar tempat) ======
async function searchGeocode(search) {
    if (search.length < 3) {
        // hindari call API terlalu sering saat input pendek
        resultsDiv.innerHTML = "";
        return;
    }

    // Menampilkan spinner loading
    document.getElementById("loading").style.display = "block"; // Menampilkan spinner

    const url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${mapPlace}/search/text?key=${apiKey}`;
    let htmlContent = "";

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                Text: search,
                MaxResults: 5,
            }),
        });

        if (!response.ok) {
            resultsDiv.innerHTML = `<div class="empty">Place/address not found.</div>`;
            return;
        }

        const data = await response.json();
        const results = data["Results"] || [];
        console.log(data);

        if (!results.length) {
            resultsDiv.innerHTML = `<div class="empty">Place/address not found.</div>`;
            return;
        }

        // Render the results
        results.forEach((r) => {
            const label = r?.Place?.Label || "Not found";
            const pt = r?.Place?.Geometry?.Point || []; // [lon, lat]
            const lon = Number(pt[0]);
            const lat = Number(pt[1]);

            if (!isFinite(lon) || !isFinite(lat)) return; // guard if data is not valid

            const { title, body } = splitLabel(label);

            const safeLabel = escapeHtml(label);
            const safeTitle = escapeHtml(title || "Title");
            const safeBody = escapeHtml(body || label);

            htmlContent += `
                        <div class="card" onclick="showLocation(${lon}, ${lat}, &quot;${safeLabel}&quot;)">
                            <div class="card-title">${safeTitle}</div>
                            <div class="card-address">${safeBody}</div>
                        </div>
                    `;
        });

        // resultsDiv.innerHTML = "<h5>This Search Data</h5><hr>" + htmlContent;
        resultsDiv.innerHTML = htmlContent;
    } catch (error) {
        resultsDiv.innerHTML = `<div class="empty">Geocoding error. Try again.</div>`;
    } finally {
        // Menyembunyikan spinner setelah pencarian selesai
        document.getElementById("loading").style.display = "none"; // Menyembunyikan spinner
        resultsDiv.style.display = "block";
    }
}
