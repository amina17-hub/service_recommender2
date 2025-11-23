<!DOCTYPE html>
<html>
<head>
    <title>Tawsiya - Artisans sur Carte</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    
    <style>
        /* Styles CSS */
        #map { height: 600px; width: 100%; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0 0 0 / 10%); }
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; direction: rtl; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; text-align: right; }
        .form-container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0 0 0 / 5%); margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; direction: rtl;}
        .form-group { display: flex; flex-direction: column; text-align: right; }
        label { margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select { padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; min-width: 150px; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background-color 0.3s; }
        button:hover { background-color: #0056b3; }
        .artisan-card { background-color: #fff; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0 0 0 / 5%); text-align: right;}
        .artisan-card strong { color: #007bff; font-size: 1.1em; }
        .score { float: left; font-weight: bold; color: #28a745; direction: ltr;}
        .note { color: #666; font-size: 0.9em; margin-top: 10px; }
        
        /* أيقونة موقع العميل (أنا) */
        .client-marker {
            background-color: red;
            border-radius: 50%;
            border: 3px solid white;
            width: 30px;
            height: 30px;
            text-align: center;
            line-height: 24px;
            color: white;
            font-size: 12px;
            font-weight: bold;
            margin-left: -15px; 
            margin-top: -15px;
        }

        /* النمط الجديد لعلامة الحرفي (صورة داخل دائرة خضراء) */
        .artisan-icon-marker {
            background-color: #28a745; 
            border: 3px solid #fff; 
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            margin-left: -22.5px; 
            margin-top: -22.5px;
        }
        .artisan-icon-marker img {
            width: 100%;
            height: 100%;
            object-fit: cover; 
            border-radius: 50%;
        }
    </style>
</head>
<body onload="initMap()">
    <h1>نظام التوصية للحرفيين (التصفية بالولاية والترتيب بالجوار)</h1>
    
    <div class="form-container">
        <div class="form-group">
            <label for="wilaya">الولاية (مثال: Skikda)</label>
            <input type="text" id="wilaya" value="Skikda">
        </div>
        
        <div class="form-group">
            <label for="service">نوع الخدمة</label>
            <select id="service">
                <option value="Maçon">بناء/ماكُون</option>
                <option value="Plombier">سباك</option>
                <option value="Electricien">كهربائي</option>
                <option value="Jardinier">بستاني</option>
                <option value="Menuisier">نجار</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="client_lat">خط العرض (Lat)</label>
            <input type="text" id="client_lat" placeholder="36.8833">
        </div>
        
        <div class="form-group">
            <label for="client_lon">خط الطول (Lon)</label>
            <input type="text" id="client_lon" placeholder="6.9">
        </div>
        
        <button onclick="searchArtisans()">بحث</button>
        <button onclick="getCurrentLocation()" style="background-color: #28a745;">حدد موقعي</button>
    </div>
    <p class="note" style="text-align: right;">* يعتمد الترتيب على المسافة من موقعك + التقييم + السعر.</p>


    <div id="map"></div> 
    
    <h2>قائمة الحرفيين المرتبة:</h2>
    <div id="recommendations-list">
        <p style="text-align: right;">الرجاء إدخال بيانات البحث والضغط على "بحث".</p>
    </div>

    <script>
        const DEFAULT_CENTER = [36.8833, 6.9]; // مركز سكيكدة الافتراضي
        let map; 
        let markers = L.layerGroup(); 
        let clientMarker = null;

        // 2. الدالة الأساسية لتهيئة الخريطة 
        function initMap() {
            if (map) {
                map.remove();
            }
            
            map = L.map('map').setView(DEFAULT_CENTER, 12);
            markers.addTo(map);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // تهيئة حقول الإحداثيات بقيمة افتراضية
            document.getElementById('client_lat').value = DEFAULT_CENTER[0];
            document.getElementById('client_lon').value = DEFAULT_CENTER[1];
        }


        // 1. دالة جلب موقع العميل التلقائي 
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude.toFixed(6);
                        const lon = position.coords.longitude.toFixed(6);
                        
                        document.getElementById('client_lat').value = lat;
                        document.getElementById('client_lon').value = lon;
                        
                        // تحديث علامة العميل على الخريطة
                        addClientLocationMarker(lat, lon);
                        
                        map.setView([lat, lon], 13); // تكبير حول الموقع الجديد
                        alert('تم تحديد موقعك بنجاح. الآن اضغط على "بحث".');
                    },
                    (error) => {
                        // حالة الرفض أو الخطأ
                        alert('تعذر تحديد الموقع التلقائي. يرجى التأكد من إعطاء الإذن للمتصفح: ' + error.message);
                    }
                );
            } else {
                alert('المتصفح لا يدعم تحديد الموقع.');
            }
        }
        
        // 3. دالة البحث الرئيسية التي يتم استدعاؤها بزر "بحث"
        function searchArtisans() {
            const clientWilaya = document.getElementById('wilaya').value;
            const serviceType = document.getElementById('service').value;
            let clientLat = parseFloat(document.getElementById('client_lat').value);
            let clientLon = parseFloat(document.getElementById('client_lon').value);
            
            if (!clientWilaya || !serviceType || isNaN(clientLat) || isNaN(clientLon)) {
                alert('الرجاء إدخال بيانات البحث والإحداثيات بشكل صحيح.');
                return;
            }
            
            document.querySelector('h2').textContent = `قائمة الحرفيين المرتبة لـ ${serviceType} في ${clientWilaya}:`;
            document.getElementById('recommendations-list').innerHTML = '<p style="text-align: right;">جاري البحث...</p>';
            
            addClientLocationMarker(clientLat, clientLon);
            
            fetchRecommendations(clientWilaya, serviceType, clientLat, clientLon);
        }

        // 4. إضافة علامة موقع العميل (النقطة الحمراء)
        function addClientLocationMarker(lat, lon) {
            const clientPos = [lat, lon];
            if (clientMarker) {
                map.removeLayer(clientMarker);
            }
            
            const redIcon = L.divIcon({
                className: 'client-marker',
                html: 'أنا',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });

            clientMarker = L.marker(clientPos, { icon: redIcon }).addTo(map)
                .bindPopup('موقع العميل').openPopup();
            
            map.setView(clientPos, 13);
        }

        // 5. دالة جلب البيانات من API
        function fetchRecommendations(clientWilaya, serviceType, clientLat, clientLon) {
            const apiUrl = `/recommendations?client_wilaya=${clientWilaya}&service_type=${serviceType}&client_lat=${clientLat}&client_lon=${clientLon}`;
            
            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const listElement = document.getElementById('recommendations-list');
                    listElement.innerHTML = ''; 
                    markers.clearLayers(); 

                    if (data.recommendations && data.recommendations.length > 0) {
                        
                        data.recommendations.forEach((artisan, index) => {
                            addMarker(artisan, index + 1);
                            addToList(artisan, index + 1);
                        });
                        
                    } else {
                        listElement.innerHTML = `<p style="color:red; text-align: right;">لم يتم العثور على حرفيين يطابقون المعايير المدخلة (${serviceType} في ${clientWilaya}).</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching recommendations:', error);
                    document.getElementById('recommendations-list').innerHTML = `<p style="color:red; text-align: right;">حدث خطأ أثناء جلب البيانات. تحقق من الكونسول (Console) ومسار API.</p>`;
                });
        }

        // 6. دالة لإضافة علامة Leaflet على الخريطة (الصورة داخل الدائرة الخضراء)
        function addMarker(artisan, rank) {
            const pos = [artisan.latitude, artisan.longitude];
            
            const iconHtml = `
                <div class="artisan-icon-marker">
                    <img src="${artisan.profile_image_url}" alt="${artisan.name}">
                </div>
            `;

            const customIcon = L.divIcon({
                className: 'custom-artisan-icon', 
                html: iconHtml,
                iconSize: [45, 45], 
                iconAnchor: [22, 22] 
            });
            
            const content = `
                <div style="direction: rtl; text-align: right; font-size: 14px; width: 200px; padding-bottom: 5px;">
                    <img src="${artisan.profile_image_url}" alt="${artisan.name}" style="width:50px; height:50px; border-radius:50%; float: left; margin-left: 10px; object-fit: cover; border: 2px solid #007bff;">
                    <strong style="color: #007bff; display: block; padding-top: 5px;">${rank}. ${artisan.name}</strong>
                    <br style="clear: both;">
                    <p style="margin: 3px 0;">📍 المسافة: ${artisan.distance_km} كم</p>
                    <p style="margin: 3px 0;">⭐ التقييم: ${artisan.rating} / 5</p>
                    <p style="margin: 3px 0;">💰 السعر: ${artisan.price} DA</p>
                    <p style="margin: 3px 0; font-weight: bold; color: #28a745;">💯 النقاط الكلية: ${artisan.total_score}</p>
                </div>
            `;
            
            L.marker(pos, { icon: customIcon }) 
              .bindPopup(content)
              .addTo(markers);
        }
        
        // 7. دالة لإضافة الحرفي إلى القائمة النصية
        function addToList(artisan, rank) {
            const listElement = document.getElementById('recommendations-list');
            const card = document.createElement('div');
            card.className = 'artisan-card';
            card.innerHTML = `
                <div class="score">الترتيب: ${rank}</div>
                <strong>${artisan.name}</strong> (${artisan.service_type})
                <br>
                <span>📍 المسافة: ${artisan.distance_km} كم</span> | 
                <span>⭐ التقييم: ${artisan.rating}</span> | 
                <span>💰 السعر: ${artisan.price} DA</span> |
                <span>💯 النقاط: ${artisan.total_score}</span>
            `;
            listElement.appendChild(card);
        }
    </script>
</body>
</html><?php /**PATH C:\Users\gueri\service-recommender\resources\views/welcome.blade.php ENDPATH**/ ?>