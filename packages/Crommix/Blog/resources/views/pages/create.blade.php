@extends('blog::layouts.lumina')

@section('title', 'Create Page')

@section('content')
    {{-- TopNavBar --}}
    <header class="fixed top-0 w-full z-50 border-b border-stone-200 bg-stone-50/90 backdrop-blur-md">
        <div class="flex justify-between items-center h-16 px-6 md:px-12 w-full mx-auto">
            <div class="flex items-center gap-base">
                <span class="text-2xl font-bold tracking-tighter text-[#1B4332]">Lumina Editorial</span>
                <span class="mx-md text-stone-200">|</span>
                <span class="font-manrope uppercase tracking-[0.1em] text-[11px] font-semibold text-stone-500">Drafting
                    Page</span>
            </div>
            <nav class="hidden md:flex items-center gap-lg">
                <a class="font-manrope uppercase tracking-[0.1em] text-[11px] font-semibold text-stone-500 hover:text-[#1B4332] transition-colors"
                    href="#">Stories</a>
                <a class="font-manrope uppercase tracking-[0.1em] text-[11px] font-semibold text-[#1B4332] border-b border-[#1B4332] pb-1"
                    href="#">Pages</a>
                <a class="font-manrope uppercase tracking-[0.1em] text-[11px] font-semibold text-stone-500 hover:text-[#1B4332] transition-colors"
                    href="#">About</a>
            </nav>
            <div class="flex items-center gap-md">
                <button
                    class="font-manrope uppercase tracking-[0.1em] text-[11px] font-semibold text-stone-500 hover:text-[#1B4332] transition-all duration-200"
                    wire:click="back()">Back</button>
                <button
                    class="bg-primary-container text-on-primary px-md py-xs rounded hover:opacity-90 transition-transform scale-95 active:opacity-80 font-manrope uppercase tracking-[0.1em] text-[11px] font-semibold"
                    wire:click="publish()">Publish</button>
            </div>
        </div>
    </header>

    {{-- SideNavBar (Editor Panel) --}}
    <aside
        class="hidden lg:flex flex-col fixed left-0 top-0 pt-20 pb-8 px-4 h-screen w-64 border-r border-stone-200 bg-stone-50">
        <div class="mb-lg">
            <h2 class="text-lg font-bold text-[#1B4332]">Editor Panel</h2>
            <p class="font-manrope text-sm font-medium text-stone-400">{{ $this->title ?? 'New Page' }}</p>
        </div>
        <nav class="flex flex-col gap-xs flex-grow">
            <a class="flex items-center gap-sm px-md py-base font-manrope text-sm font-medium text-stone-500 hover:bg-stone-50 hover:text-[#1B4332] transition-all duration-150"
                href="#">
                <span class="material-symbols-outlined" data-icon="article">article</span>
                Posts
            </a>
            <a class="flex items-center gap-sm px-md py-base font-manrope text-sm font-medium bg-stone-100 text-[#1B4332] font-bold border-l-4 border-[#1B4332] transition-all duration-150"
                href="#">
                <span class="material-symbols-outlined" data-icon="description">description</span>
                Pages
            </a>
            <a class="flex items-center gap-sm px-md py-base font-manrope text-sm font-medium text-stone-500 hover:bg-stone-50 hover:text-[#1B4332] transition-all duration-150"
                href="#">
                <span class="material-symbols-outlined" data-icon="auto_stories">auto_stories</span>
                Library
            </a>
            <a class="flex items-center gap-sm px-md py-base font-manrope text-sm font-medium text-stone-500 hover:bg-stone-50 hover:text-[#1B4332] transition-all duration-150"
                href="#">
                <span class="material-symbols-outlined" data-icon="settings">settings</span>
                Settings
            </a>
        </nav>
        <div class="mt-auto pt-lg border-t border-stone-100">
            <button
                class="w-full bg-primary-container text-on-primary py-sm rounded-lg font-manrope text-sm font-bold mb-md"
                wire:click="newPage()">New Entry</button>
            <div class="flex flex-col gap-xs">
                <a class="flex items-center gap-sm px-md py-base font-manrope text-sm font-medium text-stone-400 hover:text-[#1B4332]"
                    href="#">
                    <span class="material-symbols-outlined" data-icon="help_outline">help_outline</span>
                    Help
                </a>
                <a class="flex items-center gap-sm px-md py-base font-manrope text-sm font-medium text-stone-400 hover:text-[#1B4332]"
                    href="#">
                    <span class="material-symbols-outlined" data-icon="logout">logout</span>
                    Sign Out
                </a>
            </div>
        </div>
    </aside>

    {{-- Main Content Canvas --}}
    <main class="lg:ml-64 pt-16 flex flex-col min-h-screen">
        <div class="flex flex-col xl:flex-row flex-grow">
            {{-- Editor Surface --}}
            <section class="flex-grow flex justify-center px-6 md:px-lg py-xl">
                <div class="max-w-[720px] w-full">
                    <header class="mb-lg">
                        <input
                            class="w-full bg-transparent border-none focus:ring-0 font-h1 text-h1 text-on-surface placeholder-stone-300 p-0 mb-sm"
                            placeholder="Page Title" type="text" wire:model="title" />
                        <div class="flex items-center gap-md text-stone-400 font-label-sm border-b border-stone-100 pb-sm">
                            <span class="flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px]" data-icon="link">link</span>
                                <span wire:model="slug">lumina.editorial/{{ $slug ?? 'page-title' }}</span>
                            </span>
                            <span class="flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px]"
                                    data-icon="edit_calendar">edit_calendar</span>
                                Modified moments ago
                            </span>
                        </div>
                    </header>
                    <div class="prose prose-stone max-w-none">
                        <textarea
                            class="w-full min-h-[614px] bg-transparent border-none focus:ring-0 font-body-lg text-body-lg text-on-surface placeholder-stone-300 resize-none p-0"
                            placeholder="Start writing the story of this page..." wire:model="content"></textarea>
                    </div>

                    {{-- Contextual Toolbar (Simulated) --}}
                    <div
                        class="fixed bottom-lg left-1/2 -translate-x-1/2 lg:left-[calc(50%+128px)] bg-tertiary-container text-on-tertiary-container px-base py-xs rounded-full shadow-xl flex items-center gap-xs border border-white/10">
                        <button class="p-base hover:text-white transition-colors" onclick="document.execCommand('bold')">
                            <span class="material-symbols-outlined" data-icon="format_bold">format_bold</span>
                        </button>
                        <button class="p-base hover:text-white transition-colors" onclick="document.execCommand('italic')">
                            <span class="material-symbols-outlined" data-icon="format_italic">format_italic</span>
                        </button>
                        <button class="p-base hover:text-white transition-colors"
                            onclick="document.execCommand('formatBlock', false, 'blockquote')">
                            <span class="material-symbols-outlined" data-icon="format_quote">format_quote</span>
                        </button>
                        <div class="w-px h-md bg-stone-700 mx-xs"></div>
                        <button class="p-base hover:text-white transition-colors">
                            <span class="material-symbols-outlined" data-icon="image">image</span>
                        </button>
                        <button class="p-base hover:text-white transition-colors">
                            <span class="material-symbols-outlined" data-icon="add_link">add_link</span>
                        </button>
                    </div>
                </div>
            </section>

            {{-- Page Specific Settings Sidebar --}}
            <aside
                class="w-full xl:w-[320px] bg-surface-container-low border-l border-stone-200 p-md xl:sticky xl:top-16 xl:h-[calc(100vh-64px)]">
                <div class="flex flex-col gap-lg">
                    {{-- Publishing Section --}}
                    <div class="flex flex-col gap-md">
                        <h3 class="font-label-sm text-stone-500 uppercase tracking-widest">Publishing</h3>
                        <div class="bg-surface p-md rounded-lg border border-stone-200">
                            <div class="flex justify-between items-center mb-md">
                                <span class="font-label-md text-stone-500">Status</span>
                                <span
                                    class="font-label-md text-[#1B4332] bg-emerald-50 px-sm py-xs rounded-full">{{ $status ?? 'Draft' }}</span>
                            </div>
                            <button
                                class="w-full bg-primary-container text-on-primary py-sm rounded font-label-md hover:opacity-90 transition-all mb-sm"
                                wire:click="updatePage()">Update Page</button>
                            <button
                                class="w-full border border-stone-300 py-sm rounded font-label-md hover:bg-stone-50 transition-all"
                                wire:click="preview()">Preview Change</button>
                        </div>
                    </div>

                    {{-- Page Specifics --}}
                    <div class="flex flex-col gap-md">
                        <h3 class="font-label-sm text-stone-500 uppercase tracking-widest">Page Settings</h3>

                        {{-- URL Slug --}}
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-sm text-stone-600">URL Slug</label>
                            <div
                                class="flex items-center border-b border-stone-300 focus-within:border-[#1B4332] transition-colors py-xs">
                                <span class="text-stone-400 text-sm">/</span>
                                <input class="bg-transparent border-none focus:ring-0 text-sm w-full font-medium"
                                    type="text" wire:model="slug" />
                            </div>
                        </div>

                        {{-- Template Selection --}}
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-sm text-stone-600">Template</label>
                            <select
                                class="bg-transparent border-b border-stone-300 focus:border-[#1B4332] focus:ring-0 text-sm py-xs font-medium cursor-pointer"
                                wire:model="template">
                                <option value="standard">Standard Reading</option>
                                <option value="contact">Contact Form</option>
                                <option value="visual">Full-width Visual</option>
                                <option value="grid">Grid Index</option>
                            </select>
                        </div>

                        {{-- Featured Image Selection --}}
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-sm text-stone-600">Cover Image</label>
                            <div
                                class="group relative aspect-video w-full bg-stone-200 rounded-lg overflow-hidden cursor-pointer">
                                <img class="w-full h-full object-cover group-hover:opacity-50 transition-opacity"
                                    alt="featured image"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCDH9HJRJqK8JH-X1_mguELLh65zxis9ZwixkmqV_08Y00KPpiEGZ99TMv2WsfkfG3f6AodSFv-WKSROk3Y8Saj2I6t42dZllbqf9l0J3jvO6TlVQAlx97tVwsSgF4SKc-SPKgn7f5HWMLYBtoWAkTy0dyr852_vnRmmXMFgo9avTFCAweeoMXPlycUjiVqK22IJCge5aYJwzqXf-r2olDQW85gTgnHWh54W2CFPkPLvNQm10QpTtRksvAAZ23LvYHlR3zOzucnKcY" />
                                <div
                                    class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="material-symbols-outlined text-white"
                                        data-icon="add_a_photo">add_a_photo</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Danger Zone --}}
                    <div class="mt-auto pt-lg">
                        <button
                            class="flex items-center gap-sm text-error/60 hover:text-error transition-colors font-label-sm"
                            wire:click="discard()" wire:confirm="Are you sure?">
                            <span class="material-symbols-outlined text-[18px]" data-icon="delete">delete</span>
                            Discard Draft
                        </button>
                    </div>
                </div>
            </aside>
        </div>

        {{-- Footer --}}
        <footer class="w-full border-t border-stone-200 mt-20 bg-stone-50">
            <div class="max-w-7xl mx-auto py-12 px-6 flex flex-col md:flex-row justify-between items-center">
                <div class="flex flex-col items-center md:items-start mb-md md:mb-0">
                    <span class="text-sm font-black uppercase tracking-widest text-[#1B4332]">Lumina</span>
                    <p class="font-manrope text-xs tracking-wide text-stone-400 mt-xs">© 2024 Lumina Editorial. All rights
                        reserved.</p>
                </div>
                <div class="flex gap-lg">
                    <a class="font-manrope text-xs tracking-wide text-stone-400 hover:text-[#1B4332] transition-colors"
                        href="#">Privacy</a>
                    <a class="font-manrope text-xs tracking-wide text-stone-400 hover:text-[#1B4332] transition-colors"
                        href="#">Terms</a>
                    <a class="font-manrope text-xs tracking-wide text-stone-400 hover:text-[#1B4332] transition-colors"
                        href="#">RSS Feed</a>
                </div>
            </div>
        </footer>
    </main>
@endsection