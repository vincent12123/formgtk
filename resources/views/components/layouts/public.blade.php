<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Pendataan PTK' }} — Yayasan Harapan Karya Bangsa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <header class="border-b-4 border-accent bg-primary-dark text-white">
        <div class="mx-auto flex max-w-[1120px] items-center justify-between gap-4 px-4 py-5 md:px-8">
            <a href="{{ route('beranda') }}" class="leading-tight">
                <span class="block text-[13px] font-medium tracking-wide text-white/80">Yayasan Harapan Karya Bangsa</span>
                <span class="block font-serif text-xl font-bold text-white md:text-2xl">Pendataan PTK</span>
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-[1120px] px-4 py-6 md:px-8 md:py-10">
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-[1120px] px-4 pb-8 text-[13px] text-text-muted md:px-8">
        © {{ now()->year }} Yayasan Harapan Karya Bangsa
    </footer>
</body>
</html>
