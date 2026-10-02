<x-layouts.public title="Form Data PTK">
    <div class="mb-6">
        <a href="{{ route('beranda') }}" class="btn-ghost -ml-1 inline-flex text-sm font-semibold">&larr; Beranda</a>
        <h1 class="mt-2 text-2xl font-bold md:text-[32px]">Form Data PTK</h1>
        <p class="mt-2 text-text-muted">
            Bapak/Ibu dimohon melengkapi data identitas, domisili, kepegawaian, dan penugasan secara berurutan.
        </p>
    </div>

    @include('public.partials.ptk-form', [
        'values' => $values,
        'action' => $action,
        'schools' => $schools,
    ])
</x-layouts.public>
