import sys
import json
import os
import PyPDF2
from PyPDF2 import PageObject, Transformation

# Target standard resi dimensions: 619 x 873 px @ 150 DPI
# 619 / 150 * 72 = 297.12 pt width
# 873 / 150 * 72 = 419.04 pt height
TARGET_WIDTH = 297.12
TARGET_HEIGHT = 419.04

def parse_cli_args():
    args = sys.argv[1:]
    if len(args) < 2:
        print(json.dumps({"status": "error", "message": "Harus ada output_path dan minimal 2 input file"}))
        sys.exit(1)

    # Check if named flags are provided
    if any(a.startswith('--') for a in args):
        import argparse
        parser = argparse.ArgumentParser(description="Merge PDF files with optional standard resi sizing")
        parser.add_argument('--output', '-o', required=True, help="Path for merged output PDF")
        parser.add_argument('--standardize', '-s', default='standard_a6', choices=['standard_a6', 'original', 'true', 'false'],
                            help="Standardize page size to 619x873 px (297.12 x 419.04 pt)")
        parser.add_argument('--auto-rotate', '-r', default='1', help="Auto rotate landscape pages to portrait (1 or 0)")
        parser.add_argument('files', nargs='+', help="PDF input files to merge in order")
        pargs = parser.parse_args(args)

        std_mode = 'standard_a6' if pargs.standardize in ['standard_a6', 'true', '1'] else 'original'
        rotate = pargs.auto_rotate in ['1', 'true', 'True']
        return pargs.output, std_mode, rotate, pargs.files

    # Positional argument parsing with backwards compatibility
    output_path = args[0]
    auto_rotate = True

    if args[1] in ['standard_a6', 'original', 'true', 'false']:
        std_mode = 'standard_a6' if args[1] in ['standard_a6', 'true'] else 'original'
        files = args[2:]
    else:
        # Default behavior: standardize enabled for best resi merging experience
        std_mode = 'standard_a6'
        files = args[1:]

    return output_path, std_mode, auto_rotate, files


def main():
    output_path, standardize_mode, auto_rotate, input_files = parse_cli_args()

    if len(input_files) < 2:
        print(json.dumps({"status": "error", "message": "Minimal harus memilih 2 file PDF untuk digabungkan"}))
        sys.exit(1)

    try:
        writer = PyPDF2.PdfWriter()
        total_pages = 0
        standardized_count = 0
        file_details = []

        for fpath in input_files:
            if not os.path.exists(fpath):
                print(json.dumps({"status": "error", "message": f"File tidak ditemukan: {fpath}"}))
                sys.exit(1)

            reader = PyPDF2.PdfReader(fpath)
            file_page_count = len(reader.pages)
            total_pages += file_page_count
            file_details.append({
                "file": os.path.basename(fpath),
                "pages": file_page_count
            })

            for page in reader.pages:
                if standardize_mode == 'original':
                    # Keep raw page without any resizing or transformation
                    writer.add_page(page)
                    continue

                # ========================================================
                # Standardize to 297.12 x 419.04 pt (619 x 873 px @ 150 DPI)
                # STRICT CONSTRAINT: No cropping (Fit proportionally & center)
                # ========================================================

                # 1. Normalize existing page rotation if non-zero
                if getattr(page, 'rotation', 0) != 0:
                    page.transfer_rotation_to_content()

                orig_w = float(page.mediabox.width)
                orig_h = float(page.mediabox.height)
                orig_x = float(page.mediabox.left)
                orig_y = float(page.mediabox.bottom)

                # 2. Auto-rotate landscape orientation to portrait if requested
                if auto_rotate and orig_w > orig_h:
                    page.rotate(90)
                    page.transfer_rotation_to_content()
                    orig_w = float(page.mediabox.width)
                    orig_h = float(page.mediabox.height)
                    orig_x = float(page.mediabox.left)
                    orig_y = float(page.mediabox.bottom)

                # 3. If page already matches standard size within 0.5 pt tolerance and (0,0) origin:
                if (abs(orig_w - TARGET_WIDTH) <= 0.5 and 
                    abs(orig_h - TARGET_HEIGHT) <= 0.5 and 
                    abs(orig_x) <= 0.5 and 
                    abs(orig_y) <= 0.5):
                    # Set exact canonical mediabox to ensure 100% uniformity
                    page.mediabox.lower_left = (0, 0)
                    page.mediabox.upper_right = (TARGET_WIDTH, TARGET_HEIGHT)
                    writer.add_page(page)
                else:
                    # 4. Proportional scaling to fit inside target canvas WITHOUT CROPPING
                    scale = min(TARGET_WIDTH / orig_w, TARGET_HEIGHT / orig_h)
                    scaled_w = orig_w * scale
                    scaled_h = orig_h * scale

                    # Center the content inside the target canvas
                    tx = (TARGET_WIDTH - scaled_w) / 2.0
                    ty = (TARGET_HEIGHT - scaled_h) / 2.0

                    canvas = PageObject.create_blank_page(width=TARGET_WIDTH, height=TARGET_HEIGHT)
                    trans = Transformation().translate(-orig_x, -orig_y).scale(scale, scale).translate(tx, ty)
                    page.add_transformation(trans)
                    page.mediabox.lower_left = (0, 0)
                    page.mediabox.upper_right = (TARGET_WIDTH, TARGET_HEIGHT)
                    canvas.merge_page(page)
                    writer.add_page(canvas)
                    standardized_count += 1

        os.makedirs(os.path.dirname(output_path), exist_ok=True)
        with open(output_path, 'wb') as f_out:
            writer.write(f_out)
        writer.close()

        print(json.dumps({
            "status": "success",
            "output_file": os.path.basename(output_path),
            "output_path": output_path,
            "total_files": len(input_files),
            "total_pages": total_pages,
            "standardized_pages": standardized_count,
            "standardize_mode": standardize_mode,
            "target_dimensions": f"{TARGET_WIDTH} x {TARGET_HEIGHT} pt (619 x 873 px @ 150 DPI)",
            "files": file_details
        }))

    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))
        sys.exit(1)

if __name__ == '__main__':
    main()
