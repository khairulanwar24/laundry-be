# Perubahan API Order - Parfum dan Notes

## Ringkasan Perubahan

Telah dilakukan perubahan pada struktur data order untuk menyimpan `perfume_id` dan `notes` di level order, bukan di order item individual.

## Perubahan Struktur Data

### Sebelum (❌ Lama)

```json
{
    "customer_id": 1,
    "items": [
        {
            "service_variant_id": 1,
            "qty": 2.5,
            "note": "Handle with care" // ❌ Note di order item
        }
    ],
    "notes": "Order level notes"
}
```

### Setelah (✅ Baru)

```json
{
    "customer_id": 1,
    "perfume_id": 1, // ✅ Parfum di level order
    "notes": "Order level notes", // ✅ Notes di level order
    "items": [
        {
            "service_variant_id": 1,
            "qty": 2.5
            // ❌ Tidak ada note atau perfume di order item
        }
    ]
}
```

## API Endpoint yang Terpengaruh

### POST /outlets/{outlet}/orders

**Request Body:**

```json
{
    "customer_id": 1,
    "perfume_id": 2, // Optional: ID parfum untuk seluruh order
    "notes": "Instruksi khusus order", // Optional: Catatan untuk seluruh order
    "discount_value_snapshot": 5000, // Optional: Nilai diskon
    "items": [
        {
            "service_variant_id": 1,
            "qty": 2.5
        },
        {
            "service_variant_id": 2,
            "qty": 1
        }
    ]
}
```

**Response:**

```json
{
    "success": true,
    "message": "Order created successfully",
    "data": {
        "id": 1,
        "outlet_id": 1,
        "customer_id": 1,
        "invoice_no": "JL-25090401",
        "status": "ANTRIAN",
        "payment_status": "UNPAID",
        "perfume_id": 2, // ✅ Parfum di level order
        "notes": "Instruksi khusus order", // ✅ Notes di level order
        "discount_value_snapshot": 5000,
        "subtotal": 15000,
        "total": 10000,
        "checkin_at": "2025-09-04T10:00:00Z",
        "eta_at": null,
        "collected_at": null,
        "created_by": 1,
        "created_at": "2025-09-04T10:00:00Z",
        "updated_at": "2025-09-04T10:00:00Z",
        "customer": {
            "id": 1,
            "name": "John Doe",
            "phone": "08123456789"
        },
        "perfume": {
            // ✅ Relasi parfum
            "id": 2,
            "name": "Lavender Fresh",
            "description": "Aroma lavender yang menenangkan"
        },
        "order_items": [
            {
                "id": 1,
                "service_variant_id": 1,
                "unit": "kg",
                "qty": 2.5,
                "price_per_unit_snapshot": 5000,
                "line_total": 12500,
                "service_variant": {
                    "id": 1,
                    "name": "Cuci Kering Regular - Kiloan",
                    "unit": "kg",
                    "price_per_unit": 5000
                }
            },
            {
                "id": 2,
                "service_variant_id": 2,
                "unit": "pcs",
                "qty": 1,
                "price_per_unit_snapshot": 2500,
                "line_total": 2500,
                "service_variant": {
                    "id": 2,
                    "name": "Setrika - Per Potong",
                    "unit": "pcs",
                    "price_per_unit": 2500
                }
            }
        ]
    }
}
```

### GET /outlets/{outlet}/orders/{order}

Response structure sama seperti di atas, dengan relasi `perfume` dan `notes` di level order.

## Validasi

### Parfum (Optional)

-   Jika `perfume_id` disediakan, harus valid dan belongs to outlet yang sama
-   Jika tidak disediakan, akan disimpan sebagai `null`

### Notes (Optional)

-   Field text untuk catatan khusus order
-   Tidak ada batasan khusus selain panjang teks biasa

### Order Items

-   Tidak lagi menerima field `note` atau `perfume_id`
-   Hanya menerima `service_variant_id` dan `qty`

## Database Schema

```sql
-- orders table
CREATE TABLE orders (
    id BIGINT PRIMARY KEY,
    outlet_id BIGINT NOT NULL,
    customer_id BIGINT NOT NULL,
    perfume_id BIGINT NULL,          -- ✅ Parfum di level order
    notes TEXT NULL,                 -- ✅ Notes di level order
    discount_value_snapshot DECIMAL(12,2) DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    -- ... other fields

    FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE SET NULL
);

-- order_items table
CREATE TABLE order_items (
    id BIGINT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    service_variant_id BIGINT NOT NULL,
    unit VARCHAR(50) NOT NULL,
    qty DECIMAL(8,2) NOT NULL,
    price_per_unit_snapshot DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL
    -- ❌ Tidak ada note atau perfume_id
);
```

## Migration Impact

Aplikasi telah diupdate untuk:

1. ✅ Menyimpan `perfume_id` dan `notes` di tabel `orders`
2. ✅ Menghapus field `note` dari order item creation
3. ✅ Memvalidasi `perfume_id` belongs to outlet yang sama
4. ✅ Unit test telah diperbarui
5. ✅ Postman collection telah diperbarui

## Contoh Penggunaan

### Membuat Order dengan Parfum dan Notes

```bash
curl -X POST "http://localhost:8000/api/v1/outlets/1/orders" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "customer_id": 1,
    "perfume_id": 2,
    "notes": "Jangan terlalu panas saat setrika, bahan sensitif",
    "items": [
      {
        "service_variant_id": 1,
        "qty": 3.5
      }
    ]
  }'
```

### Membuat Order tanpa Parfum

```bash
curl -X POST "http://localhost:8000/api/v1/outlets/1/orders" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "customer_id": 1,
    "notes": "Cuci biasa saja",
    "items": [
      {
        "service_variant_id": 3,
        "qty": 2
      }
    ]
  }'
```

---

**Catatan:** Perubahan ini backward-incompatible untuk clients yang masih mengirim `note` di order items atau `perfume_id` di order items. Pastikan untuk memperbarui implementasi client sesuai dengan struktur baru.
