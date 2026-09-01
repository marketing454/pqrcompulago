<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Gestión PQR'; ?> | Compulago Pro Admin</title>
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/loading.css">
    <script src="../public/js/loading.js" defer></script>
    <style>
        :root {
            /* Compulago Official Palette */
            --compu-green: #b0c80b;
            --compu-green-bright: #c2dc0c;
            --compu-green-dark: #6e8800;
            --compu-green-soft: rgba(176, 200, 11, 0.12);
            --compu-green-gradient: linear-gradient(135deg, #b0c80b 0%, #6e8800 100%);
            
            /* Slate Dark Mode Elements */
            --sidebar-bg: #0b1329;
            --sidebar-card: #131f3d;
            --sidebar-border: rgba(255, 255, 255, 0.08);
            
            /* Admin Canvas */
            --main-bg: #f8faf9;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --border-focus: #b0c80b;
            
            /* Typography */
            --text-dark: #0f172a;
            --text-secondary: #334155;
            --text-muted: #64748b;
            
            /* Semantics */
            --danger: #ef4444;
            --success: #16a34a;
            --warning: #f59e0b;
            --info: #0284c7;
            
            /* Radii & Shadows */
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --shadow-subtle: 0 1px 3px rgba(0, 0, 0, 0.03), 0 1px 2px rgba(0, 0, 0, 0.02);
            --shadow-card: 0 10px 25px -5px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
            --shadow-green: 0 4px 14px rgba(176, 200, 11, 0.35);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--main-bg); 
            color: var(--text-dark); 
            display: flex; 
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* 21st.dev Modern Sidebar */
        .sidebar { 
            width: 270px; 
            background: var(--sidebar-bg); 
            border-right: 1px solid var(--sidebar-border); 
            color: white; 
            display: flex; 
            flex-direction: column; 
            flex-shrink: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 50;
        }

        .sidebar-header { 
            padding: 1.5rem 1.3rem; 
            border-bottom: 1px solid var(--sidebar-border); 
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            flex-shrink: 1;
            min-width: 0;
        }

        .sidebar-logo img {
            max-width: 140px;
            height: auto;
            max-height: 24px;
        }

        .sidebar-badge {
            background: var(--compu-green-soft);
            color: var(--compu-green);
            border: 1px solid rgba(176, 200, 11, 0.3);
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .sidebar-menu { 
            flex: 1; 
            padding: 1.4rem 0.9rem; 
            display: flex; 
            flex-direction: column; 
            gap: 5px;
            overflow-y: auto;
        }

        .menu-category-label {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            padding: 12px 10px 6px;
        }

        .menu-item { 
            padding: 0.8rem 1rem; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            color: #94a3b8; 
            text-decoration: none; 
            font-size: 0.88rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
        }

        .menu-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .menu-item i { 
            width: 18px; 
            font-size: 1rem;
            text-align: center; 
        }

        .menu-item:hover { 
            color: #ffffff; 
            background: rgba(255, 255, 255, 0.06); 
            transform: translateX(3px);
        }

        .menu-item.active { 
            color: #ffffff; 
            background: var(--compu-green-gradient); 
            box-shadow: 0 4px 16px rgba(176, 200, 11, 0.35);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
        }

        .menu-item.active i {
            color: #ffffff;
        }

        .sidebar-footer {
            padding: 1.2rem;
            border-top: 1px solid var(--sidebar-border);
        }

        /* Main Content Structure */
        .main-content { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            min-width: 0; 
            background: var(--main-bg);
        }

        .top-nav { 
            height: 72px; 
            background: #ffffff; 
            border-bottom: 1px solid var(--border-color); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 0 2.2rem; 
            position: sticky;
            top: 0;
            z-index: 40;
            box-shadow: var(--shadow-subtle);
        }

        .top-nav-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .top-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--text-dark);
        }

        .top-nav-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .system-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-secondary);
        }

        .user-chip { 
            display: flex; 
            align-items: center; 
            gap: 10px;
            background: #ffffff;
            border: 1.5px solid var(--border-color);
            padding: 5px 14px;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 700;
            box-shadow: var(--shadow-subtle);
        }

        .presence-dot {
            width: 8px;
            height: 8px;
            background: var(--compu-green);
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(176, 200, 11, 0.4);
        }

        .content-body {
            padding: 2.2rem;
            flex: 1;
        }

        /* 21st.dev Bento Grid Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.4rem;
            margin-bottom: 2rem;
        }

        .bento-stat-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            box-shadow: var(--shadow-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .bento-stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: transparent;
            transition: background 0.2s ease;
        }

        .bento-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-card);
            border-color: #cbd5e1;
        }

        .bento-stat-card:hover::after {
            background: var(--compu-green);
        }

        .stat-left h3 {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.03em;
            line-height: 1.1;
        }

        .stat-left p {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .bento-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .icon-green-theme { background: var(--compu-green-soft); color: var(--compu-green-dark); }
        .icon-yellow-theme { background: rgba(245, 158, 11, 0.12); color: #d97706; }
        .icon-emerald-theme { background: rgba(22, 163, 74, 0.12); color: #16a34a; }
        .icon-red-theme { background: rgba(239, 68, 68, 0.12); color: #dc2626; }

        /* Card Containers */
        .card { 
            background: #ffffff; 
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-md); 
            padding: 1.8rem; 
            margin-bottom: 1.8rem; 
            box-shadow: var(--shadow-subtle);
        }

        .card-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.4rem;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-header-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.02em;
        }

        /* 21st.dev Data Tables */
        .card-table { 
            background: #ffffff; 
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-md); 
            overflow: hidden; 
            box-shadow: var(--shadow-subtle);
            margin-bottom: 1.8rem;
        }

        table { width: 100%; border-collapse: collapse; }
        
        th { 
            text-align: left; 
            padding: 12px 14px; 
            color: var(--text-muted); 
            font-weight: 700; 
            font-size: 0.75rem; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color); 
            white-space: nowrap;
        }

        td { 
            padding: 12px 14px; 
            border-bottom: 1px solid #f1f5f9; 
            font-size: 0.85rem; 
            vertical-align: middle;
        }

        .btn-action-icon {
            width: 34px;
            height: 34px;
            border-radius: var(--radius-sm);
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            color: var(--text-secondary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-action-icon:hover {
            background: var(--compu-green-gradient);
            color: #ffffff;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(176, 200, 11, 0.35);
        }

        .nowrap-cell {
            white-space: nowrap !important;
        }

        .radicado-code-pill {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.94rem;
            font-weight: 800;
            color: var(--compu-green-dark);
            white-space: nowrap;
            display: inline-block;
            letter-spacing: 0.5px;
            transition: all 0.15s ease;
        }

        .radicado-link {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .radicado-link:hover .radicado-code-pill {
            color: #556b00;
            text-decoration: underline;
            text-shadow: 0 0 8px rgba(176, 200, 11, 0.4);
        }

        tbody tr {
            transition: background 0.15s ease;
        }

        tbody tr:hover {
            background: #fcfef7;
        }

        /* Status & Category Badges */
        .status-badge { 
            padding: 5px 12px; 
            border-radius: 9999px; 
            font-size: 0.76rem; 
            font-weight: 700; 
            display: inline-flex; 
            align-items: center; 
            gap: 7px;
            white-space: nowrap;
        }

        .pulse-dot-red {
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            animation: pulseRed 1.4s infinite cubic-bezier(0.66, 0, 0, 1);
            flex-shrink: 0;
        }

        @keyframes pulseRed {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.8);
            }
            70% {
                transform: scale(1.15);
                box-shadow: 0 0 0 7px rgba(239, 68, 68, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .bg-open { 
            background: #fef2f2; 
            color: #dc2626; 
            border: 1.5px solid #fca5a5; 
            box-shadow: 0 1px 6px rgba(239, 68, 68, 0.15);
            font-weight: 800;
        }
        
        .bg-process { background: #fef3c7; color: #b45309; border: 1.5px solid #fde68a; }
        .bg-resolved { background: #dcfce7; color: #15803d; border: 1.5px solid #bbf7d0; }
        .bg-danger { background: #f1f5f9; color: #64748b; border: 1.5px solid #e2e8f0; }

        .type-badge-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            background: #f1f5f9;
            color: var(--text-secondary);
            border: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        /* Buttons & Form Controls */
        .btn { 
            padding: 9px 18px; 
            border-radius: var(--radius-sm); 
            font-family: inherit;
            font-weight: 700; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            border: none; 
            font-size: 0.85rem; 
            transition: all 0.2s ease; 
        }

        .btn-primary { 
            background: var(--compu-green-gradient); 
            color: white; 
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2); 
            box-shadow: var(--shadow-green);
        }

        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 18px rgba(176, 200, 11, 0.45); 
        }

        .btn-view { 
            background: #f8fafc; 
            border: 1px solid #e2e8f0; 
            color: var(--text-dark); 
        }

        .btn-view:hover { 
            background: var(--compu-green-gradient); 
            color: white; 
            border-color: transparent;
            transform: translateY(-1px);
        }

        .btn-outline { 
            background: transparent; 
            border: 1.5px solid var(--border-color); 
            color: var(--text-secondary); 
        }

        .btn-outline:hover { 
            background: #f8fafc; 
            border-color: var(--compu-green); 
            color: var(--compu-green-dark);
        }

        .form-control { 
            width: 100%; 
            padding: 9px 14px; 
            border-radius: var(--radius-sm); 
            border: 1.5px solid var(--border-color); 
            font-family: inherit; 
            font-size: 0.9rem; 
            color: var(--text-dark);
            background: #ffffff;
            transition: all 0.2s ease;
        }

        .form-control:focus { 
            outline: none; 
            border-color: var(--compu-green); 
            box-shadow: 0 0 0 3.5px rgba(176, 200, 11, 0.22); 
        }

        /* Avatar Chip */
        .avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--compu-green-soft);
            color: var(--compu-green-dark);
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
