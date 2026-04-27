@extends('blog::layouts.lumina')

@section('title', 'Create Post')

@section('content')
    {{-- TopNavBar --}}
    <header class="fixed top-0 w-full z-50 border-b border-stone-200 bg-stone-50/90 backdrop-blur-md">
        <div class="flex justify-between items-center h-16 px-6 md:px-12 w-full mx-auto">
            <div class="flex items-center gap-8">
                <span class="text-2xl font-bold tracking-tighter text-[#1B4332]">Lumina Editorial</span>
                <nav class="hidden md:flex gap-6">
                    <a class="font-manrope uppercase tracking-[0.1em] text-[11px] font-semibold text-stone-500 hover:text-[#1B4332] transition-colors"
                        href="#">Stories</a>
                    <a class="font-manrope uppercase tracking-[0.1em] text-[11px] font-semibold text-stone-500 hover:text-[#1B4332] transition-colors"
                        href="#">Archive</a>
                    <a class="font-manrope uppercase tracking-[0.1em] text-[11px] font-semibold text-stone-500 hover:text-[#1B4332] transition-colors"
                        href="#">About</a>
                </nav>
            </div>
            <div class="flex items-center gap-4">
                <button
                    class="font-manrope uppercase tracking-[0.1em] text-[11px] font-semibold text-stone-500 hover:text-[#1B4332] transition-colors px-4 py-2"
                    wire:click="save('draft')">Save Draft</button>
                <button
                    class="font-manrope uppercase tracking-[0.1em] text-[11px] font-semibold bg-primary-container text-white px-6 py-2 transition-transform scale-95 active:opacity-80"
                    wire:click="save('published')">Publish</button>
            </div>
        </div>
    </header>

    {{-- SideNavBar (Editor Shell) --}}
    <aside
        class="hidden lg:flex flex-col fixed left-0 top-0 pt-20 pb-8 px-4 h-screen w-64 border-r border-stone-200 bg-stone-50">
        <div class="mb-8 px-4">
            <h2 class="text-lg font-bold text-[#1B4332] font-h2">Editor Panel</h2>
            <p class="text-xs text-stone-500 font-label-sm mt-1 uppercase">{{ $this->title ?? 'New Post' }}</p>
        </div>
        <nav class="flex-1 space-y-1">
            <a class="flex items-center gap-3 px-4 py-3 bg-stone-100 text-[#1B4332] font-bold border-l-4 border-[#1B4332] font-manrope text-sm"
                href="#">
                <span class="material-symbols-outlined" data-icon="article">article</span>
                <span>Posts</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-stone-500 hover:bg-stone-50 font-manrope text-sm transition-colors"
                href="#">
                <span class="material-symbols-outlined" data-icon="description">description</span>
                <span>Pages</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-stone-500 hover:bg-stone-50 font-manrope text-sm transition-colors"
                href="#">
                <span class="material-symbols-outlined" data-icon="auto_stories">auto_stories</span>
                <span>Library</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-stone-500 hover:bg-stone-50 font-manrope text-sm transition-colors"
                href="#">
                <span class="material-symbols-outlined" data-icon="settings">settings</span>
                <span>Settings</span>
            </a>
        </nav>
        <div class="mt-auto border-t border-stone-200 pt-6 space-y-1">
            <a class="flex items-center gap-3 px-4 py-2 text-stone-400 font-label-sm hover:text-[#1B4332] transition-colors"
                href="#">
                <span class="material-symbols-outlined" data-icon="help_outline">help_outline</span>
                <span>Help</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-2 text-stone-400 font-label-sm hover:text-[#1B4332] transition-colors"
                href="#">
                <span class="material-symbols-outlined" data-icon="logout">logout</span>
                <span>Sign Out</span>
            </a>
        </div>
    </aside>

    {{-- Main Content Area --}}
    <main class="lg:ml-64 pt-16 flex min-h-screen">
        {{-- Writing Canvas --}}
        <section class="flex-1 px-6 md:px-12 py-xl bg-surface">
            <div class="max-w-content-max mx-auto">
                {{-- Editor Toolbar --}}
                <div
                    class="mb-12 sticky top-20 z-40 bg-white/80 backdrop-blur-sm border border-stone-100 flex items-center gap-1 p-2 shadow-sm rounded-lg">
                    <button class="p-2 hover:bg-stone-100 transition-colors text-stone-600 rounded"
                        onclick="document.execCommand('bold')">
                        <span class="material-symbols-outlined" data-icon="format_bold">format_bold</span>
                    </button>
                    <button class="p-2 hover:bg-stone-100 transition-colors text-stone-600 rounded"
                        onclick="document.execCommand('italic')">
                        <span class="material-symbols-outlined" data-icon="format_italic">format_italic</span>
                    </button>
                    <button class="p-2 hover:bg-stone-100 transition-colors text-stone-600 rounded"
                        onclick="document.execCommand('formatBlock', false, 'h2')">
                        <span class="material-symbols-outlined" data-icon="title">title</span>
                    </button>
                    <div class="w-px h-6 bg-stone-200 mx-1"></div>
                    <button class="p-2 hover:bg-stone-100 transition-colors text-stone-600 rounded"
                        onclick="document.execCommand('formatBlock', false, 'blockquote')">
                        <span class="material-symbols-outlined" data-icon="format_quote">format_quote</span>
                    </button>
                    <button class="p-2 hover:bg-stone-100 transition-colors text-stone-600 rounded">
                        <span class="material-symbols-outlined" data-icon="link">link</span>
                    </button>
                    <button class="p-2 hover:bg-stone-100 transition-colors text-stone-600 rounded">
                        <span class="material-symbols-outlined" data-icon="image">image</span>
                    </button>
                    <div class="flex-1"></div>
                    <button class="p-2 hover:bg-stone-100 transition-colors text-stone-400 rounded">
                        <span class="material-symbols-outlined" data-icon="more_vert">more_vert</span>
                    </button>
                </div>

                {{-- Title Input --}}
                <div class="mb-8">
                    <textarea
                        class="w-full bg-transparent border-none p-0 focus:ring-0 font-h1 text-h1 text-on-surface placeholder-stone-300 resize-none overflow-hidden min-h-[60px]"
                        placeholder="Title of your narrative..." rows="1" wire:model="title"></textarea>
                </div>

                {{-- Body Editor --}}
                <div class="min-h-[600px] font-body-lg text-body-lg text-on-surface-variant focus:outline-none writing-surface"
                    contenteditable="true" wire:model="content">
                    <p class="mb-6">Start weaving your story here...</p>
                    <p class="text-stone-300 italic">Select text to see formatting options or use the toolbar above.</p>
                </div>
            </div>
        </section>

        {{-- Right Settings Sidebar --}}
        <aside
            class="hidden xl:block w-80 border-l border-stone-200 bg-surface-container-low p-md overflow-y-auto no-scrollbar">
            <div class="space-y-lg">
                {{-- Featured Image --}}
                <div>
                    <label class="font-label-sm text-stone-500 uppercase block mb-sm">Featured Image</label>
                    <div
                        class="aspect-video bg-stone-100 border-2 border-dashed border-stone-200 rounded flex flex-col items-center justify-center cursor-pointer hover:border-[#1B4332] transition-colors group">
                        <span class="material-symbols-outlined text-stone-300 group-hover:text-[#1B4332] mb-2"
                            data-icon="add_a_photo">add_a_photo</span>
                        <span class="text-xs font-label-md text-stone-400 group-hover:text-[#1B4332]">Click to upload</span>
                    </div>
                </div>

                {{-- Metadata Inputs --}}
                <div class="space-y-md">
                    <div>
                        <label class="font-label-sm text-stone-500 uppercase block mb-xs">Category</label>
                        <select
                            class="w-full bg-transparent border-0 border-b border-stone-200 focus:border-[#1B4332] focus:ring-0 font-label-md py-2"
                            wire:model="category">
                            <option value="">Select a category</option>
                            <option value="essays">Essays</option>
                            <option value="critiques">Critiques</option>
                            <option value="poetry">Poetry</option>
                            <option value="journal">Journal</option>
                        </select>
                    </div>
                    <div>
                        <label class="font-label-sm text-stone-500 uppercase block mb-xs">Tags</label>
                        <input
                            class="w-full bg-transparent border-0 border-b border-stone-200 focus:border-[#1B4332] focus:ring-0 font-label-md py-2"
                            placeholder="Add tags..." type="text" wire:model="tags" />
                        <div class="flex flex-wrap gap-2 mt-3">
                            @forelse(explode(',', $tags ?? '') as $tag)
                                @if(trim($tag))
                                    <span
                                        class="px-3 py-1 bg-stone-200 text-stone-600 font-label-sm rounded-full flex items-center gap-2">
                                        {{ trim($tag) }}
                                        <span class="material-symbols-outlined text-[14px] cursor-pointer"
                                            data-icon="close">close</span>
                                    </span>
                                @endif
                            @empty
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Excerpt --}}
                <div>
                    <label class="font-label-sm text-stone-500 uppercase block mb-xs">Excerpt</label>
                    <textarea
                        class="w-full bg-white border border-stone-200 focus:border-[#1B4332] focus:ring-0 font-label-md p-3 text-sm"
                        placeholder="Brief summary of the post..." rows="4" wire:model="excerpt"></textarea>
                    <p class="text-[10px] text-stone-400 mt-2 font-label-sm">Shown in search results and post cards.</p>
                </div>

                {{-- Advanced Settings Accordion --}}
                <div class="border-t border-stone-200 pt-md">
                    <button
                        class="w-full flex justify-between items-center text-stone-500 font-label-sm uppercase hover:text-[#1B4332] transition-colors">
                        <span>Advanced Settings</span>
                        <span class="material-symbols-outlined" data-icon="expand_more">expand_more</span>
                    </button>
                </div>
            </div>
        </aside>
    </main>

    {{-- Footer --}}
    <footer class="lg:ml-64 w-full border-t border-stone-200 mt-20 bg-stone-50">
        <div class="max-w-7xl mx-auto py-12 px-6 flex flex-col md:flex-row justify-between items-center">
            <div class="flex flex-col gap-2">
                <span class="text-sm font-black uppercase tracking-widest text-[#1B4332]">Lumina</span>
                <p class="font-manrope text-xs tracking-wide text-stone-400">© 2024 Lumina Editorial. All rights reserved.
                </p>
            </div>
            <div class="flex gap-8 mt-6 md:mt-0">
                <a class="font-manrope text-xs tracking-wide text-stone-400 hover:text-[#1B4332] transition-colors underline underline-offset-4"
                    href="#">Privacy</a>
                <a class="font-manrope text-xs tracking-wide text-stone-400 hover:text-[#1B4332] transition-colors underline underline-offset-4"
                    href="#">Terms</a>
                <a class="font-manrope text-xs tracking-wide text-stone-400 hover:text-[#1B4332] transition-colors underline underline-offset-4"
                    href="#">RSS Feed</a>
            </div>
        </div>
    </footer>

    {{-- Mobile Navigation Shell --}}
    <div
        class="md:hidden fixed bottom-0 left-0 right-0 bg-stone-50 border-t border-stone-200 flex justify-around items-center h-16 z-50 px-6">
        <button class="flex flex-col items-center gap-1 text-[#1B4332]">
            <span class="material-symbols-outlined" data-icon="edit_note"
                style="font-variation-settings: 'FILL' 1;">edit_note</span>
            <span class="text-[10px] font-label-sm uppercase">Write</span>
        </button>
        <button class="flex flex-col items-center gap-1 text-stone-400">
            <span class="material-symbols-outlined" data-icon="search">search</span>
            <span class="text-[10px] font-label-sm uppercase">Explore</span>
        </button>
        <button class="flex flex-col items-center gap-1 text-stone-400">
            <span class="material-symbols-outlined" data-icon="account_circle">account_circle</span>
            <span class="text-[10px] font-label-sm uppercase">Profile</span>
        </button>
    </div>
@endsection