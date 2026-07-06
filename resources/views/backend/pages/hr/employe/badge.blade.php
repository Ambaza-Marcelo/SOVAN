<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badge - {{ $data->firstname }} {{ $data->lastname }}</title>
    <link rel="stylesheet" href="{{ asset('backend/assets/css/font-awesome.min.css') }}">
    <style>
        @page {
            size: A7;
            margin: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: white;
            width: 74mm;
            height: 105mm;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        
        .badge-container {
            width: 74mm;
            height: 105mm;
            background: white;
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 8mm 6mm;
        }
        
        /* Decorative background elements */
        .badge-container::before {
            content: '';
            position: absolute;
            top: 5mm;
            right: 3mm;
            width: 20mm;
            height: 20mm;
            background: transparent;
            border: 1px solid rgba(173, 216, 230, 0.3);
            border-radius: 50%;
            opacity: 0.3;
        }
        
        /* Logo Section */
        .logo-section {
            position: relative;
            margin-bottom: 2mm;
            z-index: 1;
        }
        
        .logo-band {
            background-color: #027789;
            color: #fff;
            padding: 2mm 4mm;
            border-radius: 2mm;
            position: relative;
            margin: 0 2mm;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 10mm;
        }
        
        .logo-band::before {
            content: '';
            position: absolute;
            top: -2mm;
            left: -2mm;
            right: -2mm;
            height: 1.5mm;
            background: linear-gradient(90deg, transparent 0%, #d4af37 20%, #d4af37 80%, transparent 100%);
            border-radius: 1mm 1mm 0 0;
        }
        
        .logo-band::after {
            content: '';
            position: absolute;
            bottom: -2mm;
            left: -2mm;
            right: -2mm;
            height: 1.5mm;
            background: linear-gradient(90deg, transparent 0%, #d4af37 20%, #d4af37 80%, transparent 100%);
            border-radius: 0 0 1mm 1mm;
        }
        
        .logo-image {
            max-width: 35mm;
            max-height: 12mm;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain;
            position: relative;
            z-index: 1;
        }
        
        /* Photo Section */
        .photo-section {
            text-align: center;
            margin: 2mm 0;
            position: relative;
            z-index: 1;
        }
        
        .photo-frame {
            width: 35mm;
            height: 40mm;
            margin: 0 auto;
            border: 1mm solid #d4af37;
            border-radius: 2mm;
            overflow: hidden;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #e0e0e0 0%, #f5f5f5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        
        .photo-placeholder .fa {
            font-size: 20mm;
        }
        
        /* Name Section with Teal Band */
        .name-section {
            position: relative;
            margin: 4mm 0;
            z-index: 1;
        }
        
        .name-band {
            background-color: #027789;
            color: #fff;
            padding: 3mm 4mm;
            border-radius: 2mm;
            position: relative;
            margin: 0 2mm;
        }
        
        .name-band::before {
            content: '';
            position: absolute;
            top: -2mm;
            left: -2mm;
            right: -2mm;
            height: 1.5mm;
            background: linear-gradient(90deg, transparent 0%, #d4af37 20%, #d4af37 80%, transparent 100%);
            border-radius: 1mm 1mm 0 0;
        }
        
        .name-band::after {
            content: '';
            position: absolute;
            bottom: -2mm;
            left: -2mm;
            right: -2mm;
            height: 1.5mm;
            background: linear-gradient(90deg, transparent 0%, #d4af37 20%, #d4af37 80%, transparent 100%);
            border-radius: 0 0 1mm 1mm;
        }
        
        .name-text {
            color: white;
            text-align: center;
            font-weight: bold;
            font-size: 4.5mm;
            line-height: 1.3;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .name-text .first-name {
            display: block;
            margin-bottom: 1mm;
        }
        
        .name-text .last-name {
            display: block;
        }
        
        /* Decorative leaf on left side */
        .name-section::before {
            content: '❋';
            position: absolute;
            left: -1mm;
            top: 50%;
            transform: translateY(-50%) rotate(-45deg);
            color: rgba(173, 216, 230, 0.4);
            font-size: 6mm;
            z-index: 0;
        }
        
        /* Staff Section */
        .staff-section {
            text-align: center;
            margin: 3mm 0;
            position: relative;
            z-index: 1;
        }
        
        .staff-label {
            color: #027789;
            font-size: 6mm;
            font-weight: bold;
            font-family: 'Times New Roman', serif;
            position: relative;
            display: inline-block;
            padding: 0 8mm;
        }
        
        .staff-label::before,
        .staff-label::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 6mm;
            height: 0.5px;
            background: #027789;
        }
        
        .staff-label::before {
            left: -8mm;
        }
        
        .staff-label::after {
            right: -8mm;
        }
        
        /* ID Number Section */
        .id-section {
            text-align: center;
            margin-top: auto;
            padding-top: 2mm;
            z-index: 1;
        }
        
        .id-number {
            color: #027789;
            font-size: 3.5mm;
            font-weight: normal;
            letter-spacing: 0.5px;
        }
        
        @media print {
            body {
                margin: 0;
            }
            
            .badge-container {
                page-break-after: always;
            }
            
            @page {
                size: A7;
                margin: 0;
            }
        }
        
        /* Print button (hidden when printing) */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
        
        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="badge-container">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo-band">
                <img src="{{ asset('img/eden_logo.png') }}" alt="Eden Garden Resort" class="logo-image">
            </div>
        </div>
        
        <!-- Photo Section -->
        <div class="photo-section">
            <div class="photo-frame">
                @if($data->image && isset($imageUrl) && $imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $data->firstname }} {{ $data->lastname }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="photo-placeholder" style="display: none;">
                        <i class="fa fa-user"></i>
                    </div>
                @else
                    <div class="photo-placeholder">
                        <i class="fa fa-user"></i>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Name Section -->
        <div class="name-section">
            <div class="name-band">
                <div class="name-text">
                    <span class="first-name">{{ strtoupper($data->firstname) }}</span>
                    <span class="last-name">{{ strtoupper($data->lastname) }}</span>
                </div>
            </div>
        </div>
        
        <!-- Staff Section -->
        <div class="staff-section">
            <div class="staff-label">@lang('messages.STAFF')</div>
        </div>
        
        <!-- ID Number Section -->
        <div class="id-section">
            <div class="id-number">ID No.#{{ $data->matricule_no }}</div>
        </div>
    </div>
    
    <button class="print-button" onclick="window.print()">
        <i class="fa fa-print"></i> Imprimer
    </button>
    
    <script>
        // Auto-print option (uncomment if needed)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>

