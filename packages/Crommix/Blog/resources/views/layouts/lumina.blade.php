<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title') - Lumina Editorial</title>

    {{-- Google Fonts --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />

    {{-- Tailwind CSS --}}
    @vite(['resources/css/filament/admin/theme.css'])
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-variant": "#e4e2e2",
                        "on-primary": "#ffffff",
                        "surface": "#fbf9f8",
                        "secondary-container": "#e5e2e1",
                        "on-secondary-fixed-variant": "#474646",
                        "secondary-fixed": "#e5e2e1",
                        "tertiary": "#242625",
                        "surface-dim": "#dbdad9",
                        "secondary-fixed-dim": "#c8c6c5",
                        "on-secondary": "#ffffff",
                        "outline": "#717973",
                        "tertiary-container": "#3a3c3b",
                        "inverse-surface": "#303031",
                        "outline-variant": "#c1c8c2",
                        "surface-container-lowest": "#ffffff",
                        "on-primary-container": "#86af99",
                        "inverse-primary": "#a5d0b9",
                        "error": "#ba1a1a",
                        "primary-container": "#1b4332",
                        "on-primary-fixed-variant": "#274e3d",
                        "on-tertiary": "#ffffff",
                        "on-tertiary-container": "#a5a6a5",
                        "on-error-container": "#93000a",
                        "error-container": "#ffdad6",
                        "on-tertiary-fixed-variant": "#454746",
                        "on-secondary-fixed": "#1c1b1b",
                        "surface-container-highest": "#e4e2e2",
                        "on-primary-fixed": "#002114",
                        "surface-container": "#efeded",
                        "on-error": "#ffffff",
                        "background": "#fbf9f8",
                        "secondary": "#5f5e5e",
                        "surface-tint": "#3f6653",
                        "surface-container-low": "#f5f3f3",
                        "tertiary-fixed": "#e2e3e1",
                        "on-surface": "#1b1c1c",
                        "on-surface-variant": "#414844",
                        "surface-bright": "#fbf9f8",
                        "on-background": "#1b1c1c",
                        "surface-container-high": "#e9e8e7",
                        "primary": "#012d1d",
                        "primary-fixed": "#c1ecd4",
                        "tertiary-fixed-dim": "#c6c7c5",
                        "inverse-on-surface": "#f2f0f0",
                        "on-tertiary-fixed": "#1a1c1b",
                        "on-secondary-container": "#656464",
                        "primary-fixed-dim": "#a5d0b9"
                    },
                    borderRadius: {
                        DEFAULT: "0.125rem",
                        lg: "0.25rem",
                        xl: "0.5rem",
                        full: "0.75rem"
                    },
                    spacing: {
                        lg: "48px",
                        "content-max": "720px",
                        base: "8px",
                        sm: "12px",
                        xl: "80px",
                        xs: "4px",
                        md: "24px",
                        "container-max": "1120px"
                    },
                    fontFamily: {
                        "body-lg": ["Newsreader"],
                        "label-sm": ["Manrope"],
                        "body-md": ["Newsreader"],
                        h3: ["Manrope"],
                        h2: ["Manrope"],
                        "label-md": ["Manrope"],
                        h1: ["Manrope"]
                    },
                    fontSize: {
                        "body-lg": ["21px", { lineHeight: "1.6", letterSpacing: "0", fontWeight: "400" }],
                        "label-sm": ["12px", { lineHeight: "1.2", letterSpacing: "0.08em", fontWeight: "600" }],
                        "body-md": ["18px", { lineHeight: "1.6", letterSpacing: "0", fontWeight: "400" }],
                        h3: ["24px", { lineHeight: "1.3", letterSpacing: "0", fontWeight: "600" }],
                        h2: ["32px", { lineHeight: "1.2", letterSpacing: "-0.01em", fontWeight: "600" }],
                        "label-md": ["14px", { lineHeight: "1.4", letterSpacing: "0.05em", fontWeight: "500" }],
                        h1: ["48px", { lineHeight: "1.1", letterSpacing: "-0.02em", fontWeight: "700" }]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
        }

        .writing-surface:focus {
            outline: none;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>

<body class="bg-surface text-on-surface font-body-md selection:bg-primary-fixed selection:text-primary">
    @yield('content')
</body>

</html>