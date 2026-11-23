import sys
import json
import re
import os
import PyPDF2

def clean_text(text):
    if not text:
        return ""
    text = re.sub(r'\s+', ' ', text)
    # Fix single-letter splits like 'T omato' -> 'Tomato', 'V olly' -> 'Volly'
    text = re.sub(r'\b([A-Z])\s+([a-z]+)\b', r'\1\2', text)
    return text.strip()

def clean_sku(text):
    if not text:
        return ""
    # In Shopee waybills, SKUs are compact codes (e.g. BaffOceanWaves, BandanaGrayRain).
    # Multi-line word wrapping and font kerning in PDFs create spaces or line breaks.
    # Stripping all whitespace reconstructs the complete SKU code.
    return re.sub(r'\s+', '', text).strip()

def normalize_variant(text):
    if not text:
        return ""
    clean = clean_text(text).strip()
    if clean.lower() in ['default', '-', 'none', 'standar', 'standard', 'null', 'kosong', 'normal']:
        return ""
    return clean

def parse_shopee_page(page, page_num):
    parts = []
    def visitor(text, cm, tm, fontDict, fontSize):
        if text.strip():
            parts.append({'x': round(tm[4], 1), 'y': round(tm[5], 1), 'text': text.strip()})
    
    page.extract_text(visitor_text=visitor)
    parts.sort(key=lambda item: (item['y'], item['x']))

    full_text = " ".join([p['text'] for p in parts])
    
    # Resi Number
    waybill_no = ""
    resi_match = re.search(r'No\.\s*Resi:\s*([A-Z0-9]+)', full_text)
    if resi_match:
        waybill_no = resi_match.group(1)
    else:
        spx_match = re.search(r'(SPXID[A-Z0-9]+)', full_text)
        if spx_match:
            waybill_no = spx_match.group(1)

    # Order Number
    order_no = ""
    order_match = re.search(r'No\.Pesanan:\s*([A-Z0-9]+)', full_text)
    if order_match:
        order_no = order_match.group(1)
    else:
        pesan_match = re.search(r'Pesan:\s*\(([A-Z0-9]+)\)', full_text)
        if pesan_match:
            order_no = pesan_match.group(1)

    # Sender Name
    raw_text = page.extract_text() or ""
    raw_lower = (raw_text + " " + full_text).lower()
    sender = ""
    if "moveda" in raw_lower or "bandana buff" in raw_lower:
        sender = "Moveda Activewear Indonesia"
    elif "aruna" in raw_lower:
        sender = "Aruna Sportswear Indonesia"
    else:
        sender_match = re.search(r'Pengirim:\s*([A-Za-z0-9\s]+?)(?:628|\d{9,}|KOTA|Jl|\n|Penerima)', full_text)
        if sender_match and not any(kw in sender_match.group(1).lower() for kw in ['pesanan', 'tidak', 'ya', 'cod']):
            sender = clean_text(sender_match.group(1))

    # Recipient Name
    recipient = ""
    rec_match = re.search(r'Penerima:\s*([A-Za-z0-9\s\*\.]+?)(?:HOME|OFFICE|Jl|Jalan|RT|RW|\d{5,}|KAB|KOTA)', full_text)
    if rec_match:
        recipient = clean_text(rec_match.group(1))

    # City / Destination
    city = ""
    city_match = re.search(r'(KAB\.\s*[A-Z\s]+|KOTA\s*[A-Z\s]+)', full_text)
    if city_match:
        city = clean_text(city_match.group(1))

    # Find items table
    start_y = None
    end_y = None
    for p in parts:
        if p['text'] == '#' or 'Nama Produk' in p['text']:
            start_y = p['y']
        elif 'Pesan:' in p['text']:
            end_y = p['y']
            break

    items = []
    if start_y is not None:
        table_parts = [p for p in parts if start_y < p['y'] < (end_y if end_y else 999999)]
        current_item = None
        for p in table_parts:
            # Row index number at x <= 18
            if p['x'] <= 18 and p['text'].isdigit():
                if current_item:
                    items.append(current_item)
                current_item = {
                    'product_name': '',
                    'sku': '',
                    'variant': '',
                    'qty': 1
                }
            elif current_item:
                x = p['x']
                txt = p['text']
                if x < 210:
                    current_item['product_name'] = (current_item['product_name'] + ' ' + txt).strip()
                elif 210 <= x < 275:
                    current_item['sku'] = (current_item['sku'] + ' ' + txt).strip()
                elif 275 <= x < 355:
                    current_item['variant'] = (current_item['variant'] + ' ' + txt).strip()
                elif x >= 355:
                    try:
                        current_item['qty'] = int(txt)
                    except:
                        pass
        if current_item:
            items.append(current_item)

    # Clean items
    for it in items:
        it['product_name'] = clean_text(it['product_name'])
        it['sku'] = clean_sku(it['sku'])
        it['variant'] = normalize_variant(it['variant'])

    return {
        "page": page_num,
        "platform": "Shopee",
        "courier": "SPX Express",
        "waybill_no": waybill_no,
        "order_no": order_no,
        "sender": sender,
        "recipient": recipient,
        "city": city,
        "items": items,
        "total_qty": sum(it['qty'] for it in items)
    }

def parse_tiktok_page(page, page_num):
    parts = []
    def visitor(text, cm, tm, fontDict, fontSize):
        if text.strip():
            parts.append({'x': round(tm[4], 1), 'y': round(tm[5], 1), 'text': text.strip()})
    
    page.extract_text(visitor_text=visitor)
    parts.sort(key=lambda item: (item['y'], item['x']))

    full_text = " ".join([p['text'] for p in parts])

    # Resi Number (J&T: starts with JY, e.g. JY2007017459)
    waybill_no = ""
    resi_match = re.search(r'\b(JY\d{10,})\b', full_text)
    if resi_match:
        waybill_no = resi_match.group(1)

    # Order Number
    order_no = ""
    order_match = re.search(r'Order\s*ID:\s*(\d+)', full_text, re.IGNORECASE)
    if order_match:
        order_no = order_match.group(1)
    else:
        order_match2 = re.search(r'Order\s*Id\s*:\s*(\d+)', full_text, re.IGNORECASE)
        if order_match2:
            order_no = order_match2.group(1)

    # Sender Name
    raw_text = page.extract_text() or ""
    raw_lower = (raw_text + " " + full_text).lower()
    sender = ""
    if "moveda" in raw_lower or "bandana buff" in raw_lower:
        sender = "Moveda Activewear Indonesia"
    elif "aruna" in raw_lower:
        sender = "Aruna Sportswear Indonesia"
    else:
        sender_match = re.search(r'Pengirim\s*:\s*([^\n|]+)', raw_text, re.IGNORECASE)
        if sender_match:
            sender = clean_text(sender_match.group(1))

    # Recipient Name
    recipient = ""
    rec_match = re.search(r'Penerima\s*:\s*([A-Za-z0-9\s\*\.]+?)(?:\(\+62\)|JAWA|PERUMAHAN|Dusun|Jl|Jalan)', full_text)
    if rec_match:
        recipient = clean_text(rec_match.group(1))

    # City / Destination
    city = ""
    city_match = re.search(r'JAWA\s+[A-Z]+,([A-Z\s]+)|KOTA\s+([A-Z\s]+)', full_text)
    if city_match:
        city = clean_text(city_match.group(1) or city_match.group(2))

    # Items table
    # In Tokopedia/TikTok Shop:
    # Header: Product Name (x~237), SKU (x~700), Seller SKU (x~1038), Qty (x~1398)
    # Ends at Qty Total:
    start_y = None
    end_y = None
    for p in parts:
        if 'Product Name' in p['text']:
            start_y = p['y']
        elif 'Qty Total:' in p['text'] or 'Order ID:' in p['text']:
            if start_y and p['y'] > start_y:
                end_y = p['y']
                break

    items = []
    if start_y is not None:
        table_parts = [p for p in parts if start_y < p['y'] < (end_y if end_y else 999999)]
        
        # Cluster lines by Y (within 15 units)
        lines_by_y = {}
        for p in table_parts:
            matched_y = None
            for y in lines_by_y:
                if abs(y - p['y']) < 18:
                    matched_y = y
                    break
            if matched_y is None:
                matched_y = p['y']
                lines_by_y[matched_y] = []
            lines_by_y[matched_y].append(p)

        current_item = None
        for y, l_parts in sorted(lines_by_y.items()):
            l_parts.sort(key=lambda p: p['x'])
            
            # Check if this line has a Qty number at x >= 1250
            qty_part = next((p for p in l_parts if p['x'] >= 1250 and p['text'].isdigit()), None)
            
            if qty_part:
                # Save previous item
                if current_item:
                    items.append(current_item)
                
                # Start new item
                qty_val = int(qty_part['text'])
                seller_sku = ""
                variant = ""
                name_words = []
                
                for p in l_parts:
                    if p['x'] >= 1250:
                        continue
                    elif 950 <= p['x'] < 1250:
                        seller_sku = (seller_sku + ' ' + p['text']).strip()
                    elif 650 <= p['x'] < 950:
                        variant = (variant + ' ' + p['text']).strip()
                    elif p['x'] < 650:
                        name_words.append(p['text'])
                
                current_item = {
                    'product_name': ' '.join(name_words).strip(),
                    'sku': seller_sku,        # In TikTok, Seller SKU is the real SKU code
                    'variant': variant,       # In TikTok, "SKU" column holds the variant
                    'qty': qty_val
                }
            elif current_item:
                # Continuation line of product name, variant, or seller sku
                for p in l_parts:
                    if p['x'] < 650:
                        current_item['product_name'] = (current_item['product_name'] + ' ' + p['text']).strip()
                    elif 650 <= p['x'] < 950:
                        current_item['variant'] = (current_item['variant'] + ' ' + p['text']).strip()
                    elif 950 <= p['x'] < 1250:
                        current_item['sku'] = (current_item['sku'] + ' ' + p['text']).strip()

        if current_item:
            items.append(current_item)

    for it in items:
        it['product_name'] = clean_text(it['product_name'])
        it['sku'] = clean_text(it['sku'])
        it['variant'] = normalize_variant(it['variant'])
        # Fallback: if Seller SKU was blank in resi, but SKU column has value
        if not it['sku'] and it['variant']:
            it['sku'] = it['variant']
            it['variant'] = ""

    return {
        "page": page_num,
        "platform": "TikTok Shop",
        "courier": "J&T Express",
        "waybill_no": waybill_no,
        "order_no": order_no,
        "sender": sender,
        "recipient": recipient,
        "city": city,
        "items": items,
        "total_qty": sum(it['qty'] for it in items)
    }

def detect_and_parse_page(page, page_num):
    text = page.extract_text() or ""
    # Check platform keywords
    if "Shopee" in text or "SPXID" in text or "STD" in text or "#Nama Produk" in text:
        return parse_shopee_page(page, page_num)
    elif "tokopedia" in text or "Product Name" in text or "Seller SKU" in text or "In transit by:" in text:
        return parse_tiktok_page(page, page_num)
    else:
        # Fallback check
        if "SPX" in text:
            return parse_shopee_page(page, page_num)
        return parse_tiktok_page(page, page_num)

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"status": "error", "message": "No file path provided"}))
        sys.exit(1)

    pdf_path = sys.argv[1]
    if not os.path.exists(pdf_path):
        print(json.dumps({"status": "error", "message": f"File not found: {pdf_path}"}))
        sys.exit(1)

    try:
        reader = PyPDF2.PdfReader(pdf_path)
        total_pages = len(reader.pages)
        raw_pages = []

        for i, page in enumerate(reader.pages):
            wb = detect_and_parse_page(page, i + 1)
            raw_pages.append(wb)

        # Merge multi-page waybills (e.g. orders with many items spanning across pages)
        waybills = []
        for wb in raw_pages:
            # Skip completely empty pages with no items and no waybill number
            if not wb['items'] and not wb['waybill_no'] and not wb['order_no']:
                continue

            is_continuation = False
            if waybills:
                prev = waybills[-1]
                # Condition 1: Same non-empty order number
                if wb['order_no'] and prev['order_no'] and wb['order_no'] == prev['order_no']:
                    is_continuation = True
                # Condition 2: Same non-empty waybill number
                elif wb['waybill_no'] and prev['waybill_no'] and wb['waybill_no'] == prev['waybill_no']:
                    is_continuation = True
                # Condition 3: No waybill_no and no order_no on this page, but it has item rows following a valid waybill
                elif not wb['waybill_no'] and not wb['order_no'] and wb['items']:
                    is_continuation = True

            if is_continuation:
                prev = waybills[-1]
                prev['items'].extend(wb['items'])
                prev['total_qty'] = sum(it['qty'] for it in prev['items'])
                if not prev['waybill_no'] and wb['waybill_no']:
                    prev['waybill_no'] = wb['waybill_no']
                if not prev['recipient'] and wb['recipient']:
                    prev['recipient'] = wb['recipient']
                if not prev['sender'] and wb['sender']:
                    prev['sender'] = wb['sender']
                if not prev['city'] and wb['city']:
                    prev['city'] = wb['city']
            else:
                waybills.append(wb)

        # Summary by SKU & Variant
        summary = {}
        for wb in waybills:
            for item in wb['items']:
                # Key for aggregation
                key = f"{item['sku']}||{item['variant']}||{item['product_name'][:30]}"
                if key not in summary:
                    summary[key] = {
                        "sku": item['sku'],
                        "variant": item['variant'],
                        "product_name": item['product_name'],
                        "total_qty": 0,
                        "platforms": set()
                    }
                summary[key]['total_qty'] += item['qty']
                summary[key]['platforms'].add(wb['platform'])

        # Convert set to list for json serialization
        summary_list = []
        for v in summary.values():
            v['platforms'] = list(v['platforms'])
            summary_list.append(v)
        summary_list.sort(key=lambda x: -x['total_qty'])

        result = {
            "status": "success",
            "file": os.path.basename(pdf_path),
            "total_pages": total_pages,
            "total_waybills": len(waybills),
            "total_qty": sum(wb['total_qty'] for wb in waybills),
            "waybills": waybills,
            "summary": summary_list
        }
        print(json.dumps(result, ensure_ascii=False))

    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))
        sys.exit(1)

if __name__ == '__main__':
    main()

