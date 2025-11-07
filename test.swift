import SwiftUI
import MapLibre
import CoreLocation

// Membungkus MGLMapView dalam UIViewRepresentable untuk digunakan di SwiftUI
struct ContentView: View {
    
    @State private var mapStyle = ""  // State untuk menyimpan map style (Gaya peta)

    // ▼▼▼ GANTI BAGIAN INI DENGAN INFORMASI DARI AKUN AWS ANDA ▼▼▼
    var region = "YOUR_REGION"  // Wilayah untuk API AWS (misalnya "us-east-1")
    var nameMAP = "YOUR_NAME_MAP"  // Nama peta yang digunakan di AWS
    var placeMAP = "YOUR_PLACE_MAP"  // Nama indeks tempat di AWS
    var apiKey = "YOUR_API_KEY"  // API key untuk mengakses AWS API
    // ▲▲▲ PASTIKAN SEMUA NILAI DI ATAS SUDAH DIGANTI ▲▲▲


    @State private var searchQuery = "" // State untuk teks pencarian (misalnya nama tempat)

    var body: some View {
        VStack {
            // Menampilkan Peta
            MapView(mapStyle: $mapStyle, region: region, nameMAP: nameMAP, apiKey: apiKey)
                .edgesIgnoringSafeArea(.all) // Peta mengisi layar penuh tanpa ada ruang di tepi layar

            // Menampilkan input pencarian
            VStack {
                TextField("Cari tempat...", text: $searchQuery)  // Kolom teks untuk memasukkan query pencarian
                    .padding()
                    .background(Color.white)
                    .cornerRadius(8)
                    .padding()

                Button("Search") {  // Tombol untuk memulai pencarian tempat
                    searchPlace(query: searchQuery)
                }
                .padding()
            }
            .background(Color.white)
            .cornerRadius(8)
            .padding()
        }
    }

    // Fungsi untuk mencari tempat di AWS
    func searchPlace(query: String) {
        guard !query.isEmpty else { return }  // Jika query kosong, keluar dari fungsi

        let url = URL(string: "https://places.geo.\(region).amazonaws.com/places/v0/indexes/\(placeMAP)/search/text?key=\(apiKey)")!

        var request = URLRequest(url: url)
        request.httpMethod = "POST"  // Metode HTTP untuk permintaan ini adalah POST
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")  // Menetapkan header content type

        // Membuat body dari permintaan (berisi teks pencarian dan jumlah hasil yang diinginkan)
        let body: [String: Any] = [
            "Text": query,  // Teks pencarian
            "MaxResults": 5  // Menentukan jumlah maksimal hasil pencarian
        ]

        request.httpBody = try? JSONSerialization.data(withJSONObject: body, options: [])  // Mengonversi body menjadi JSON

        // Membuat task untuk mengirimkan permintaan
        let task = URLSession.shared.dataTask(with: request) { data, response, error in
            guard let data = data else { return }  // Memastikan data diterima

            do {
                // Mencoba mengonversi data JSON yang diterima
                if let json = try JSONSerialization.jsonObject(with: data, options: []) as? [String: Any],
                   let results = json["Results"] as? [[String: Any]] {
                    for place in results {
                        // Mengambil informasi tempat, seperti label (nama tempat) dan koordinat
                        if let label = place["Label"] as? String,
                           let geometry = place["Geometry"] as? [String: Any],
                           let point = geometry["Point"] as? [Double], point.count == 2 {
                            let lat = point[1]  // Latitude
                            let lon = point[0]  // Longitude
                            print("Found: \(label), Lat: \(lat), Lon: \(lon)")  // Menampilkan hasil di konsol
                        }
                    }
                }
            } catch {
                print("Failed to load data: \(error)")  // Menangani error jika terjadi masalah dengan data
            }
        }

        task.resume()  // Memulai task yang telah dibuat
    }
}

// Membungkus MGLMapView dalam UIViewRepresentable untuk digunakan di SwiftUI
struct MapView: UIViewRepresentable {
    @Binding var mapStyle: String  // Binding untuk map style
    var region: String  // Wilayah untuk peta (dari AWS)
    var nameMAP: String  // Nama peta (dari AWS)
    var apiKey: String  // API key untuk mengakses peta

    // Fungsi untuk membuat MGLMapView
    func makeUIView(context: Context) -> MGLMapView {
        let mapView = MGLMapView(frame: .zero)
        mapView.delegate = context.coordinator  // Menetapkan coordinator sebagai delegasi peta
        mapView.setCenter(CLLocationCoordinate2D(latitude: 1.3521, longitude: 103.8198), zoomLevel: 13, animated: false)
        return mapView
    }

    // Fungsi untuk memperbarui MGLMapView
    func updateUIView(_ uiView: MGLMapView, context: Context) {
        if !mapStyle.isEmpty {  // Jika gaya peta tersedia, terapkan ke peta
            uiView.styleURL = URL(string: mapStyle)
        }
    }

    // Membuat Coordinator untuk menangani event map
    func makeCoordinator() -> Coordinator {
        return Coordinator(self)  // Mengembalikan instance Coordinator
    }

    // Coordinator untuk menangani event map seperti pemuatan gaya peta
    class Coordinator: NSObject, MGLMapViewDelegate {
        var parent: MapView

        init(_ parent: MapView) {
            self.parent = parent
        }

        // Fungsi untuk load map style
        func mapViewDidFinishLoadingMap(_ mapView: MGLMapView) {
            self.loadMapStyle(mapView)  // Panggil fungsi untuk memuat gaya peta
        }

        // Fungsi untuk mengambil gaya peta dari AWS
        func loadMapStyle(_ mapView: MGLMapView) {
            let url = URL(string: "https://maps.geo.\(parent.region).amazonaws.com/maps/v0/maps/\(parent.nameMAP)
            // /style-descriptor?key=\(parent.apiKey)")!  // URL untuk mengambil gaya peta

            let task = URLSession.shared.dataTask(with: url) { data, response, error in
                guard let data = data else { return }

                do {
                    if let json = try JSONSerialization.jsonObject(with: data, options: []) as? [String: Any] {
                        self.parent.mapStyle = String(data: data, encoding: .utf8) ?? ""  // Menyimpan gaya peta
                    }
                } catch {
                    print("Failed to load map style: \(error)")  // Menangani error jika terjadi masalah saat mengambil gaya peta
                }
            }

            task.resume()  // Memulai task untuk mengambil gaya peta
        }
    }
}

struct ContentView_Previews: PreviewProvider {
    static var previews: some View {
        ContentView()  // Menampilkan preview untuk ContentView
    }
}
