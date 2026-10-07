<footer class="w-full bg-surface-container-lowest shadow-[0_-1px_8px_rgba(0,0,0,0.03)]">
    <div class="max-w-7xl mx-auto px-6 lg:px-12 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12">
            <!-- Brand & Accreditation -->
            <div class="lg:col-span-2 flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex flex-col">
                        <span class="font-headline-sm text-headline-sm text-on-surface">Bursa Kerja Khusus</span>
                        <span class="font-label-dense text-label-dense text-on-surface-variant">SMK Plus Pelita Nusantara</span>
                    </div>
                </div>
                <p class="font-body-default text-body-default text-on-surface-variant pr-4">
                    Lembaga penyalur kerja dan praktik kerja lapangan (PKL) resmi SMK Plus Pelita Nusantara. Mempersiapkan lulusan vokasi yang kompeten, berdaya saing global, dan siap terserap di dunia industri.
                </p>
                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-surface-container font-label-dense text-label-dense text-on-surface">
                        <span class="material-symbols-outlined text-[16px] mr-1.5 text-secondary-container">verified</span>Terakreditasi A BAN-SM
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-surface-container font-label-dense text-label-dense text-on-surface">
                        <span class="material-symbols-outlined text-[16px] mr-1.5 text-primary-container">hub</span>Link &amp; Match Kemendikbudristek
                    </span>
                </div>
            </div>

            <!-- Akses Cepat (Sitemap links) -->
            <div class="flex flex-col gap-3">
                <span class="font-label-dense text-label-dense uppercase tracking-wider text-on-surface-variant">Akses Cepat</span>
                <ul class="flex flex-col gap-2.5 font-label-md text-label-md text-on-surface-variant">
                    <li><a class="hover:text-on-surface transition-colors" data-path="beranda" href="{{ url('/bkk') }}">Beranda Utama</a></li>
                    <li><a class="hover:text-on-surface transition-colors" data-path="lowongan-pkl-&-kerja" href="{{ url('/bkk/lowongan') }}">Eksplorasi Lowongan</a></li>
                    <li><a class="hover:text-on-surface transition-colors" data-path="berita-&-agenda" href="{{ url('/bkk/berita') }}">Agenda &amp; Berita</a></li>
                    <li><a class="hover:text-on-surface transition-colors" data-path="kerja-sama-mitra" href="{{ url('/bkk/kerja-sama') }}">Registrasi Mitra IDUKA</a></li>
                    <li><a class="hover:text-on-surface transition-colors" data-path="tentang-bkk" href="{{ url('/bkk/tentang') }}">Tentang BKK &amp; Struktur</a></li>
                    <li><a class="hover:text-on-surface transition-colors" href="{{ route('bkk.admin.index') }}">Dashboard Admin</a></li>
                </ul>
            </div>

            <!-- Kemitraan & IDUKA -->
            <div class="flex flex-col gap-3">
                <span class="font-label-dense text-label-dense uppercase tracking-wider text-on-surface-variant">Kemitraan &amp; IDUKA</span>
                <p class="font-body-dense text-body-dense text-on-surface-variant">
                    Buka peluang rekrutmen talenta muda unggulan dan program magang industri bersertifikat bersama kami.
                </p>
                <a class="inline-flex items-center font-label-md text-label-md text-primary font-semibold hover:text-on-surface transition-colors" data-path="kerja-sama-mitra" href="{{ url('/bkk/kerja-sama') }}">
                    <span class="material-symbols-outlined text-[18px] mr-1.5">handshake</span>Ajukan MoU Kemitraan
                </a>
                <div class="p-3 rounded-DEFAULT bg-surface-container flex flex-col gap-1">
                    <span class="font-label-dense text-label-dense text-on-surface">Email Khusus Mitra</span>
                    <span class="font-body-dense text-body-dense text-on-surface-variant">kemitraan@smkpenus.sch.id</span>
                </div>
            </div>

            <!-- Sekretariat BKK -->
            <div class="flex flex-col gap-3">
                <span class="font-label-dense text-label-dense uppercase tracking-wider text-on-surface-variant">Sekretariat BKK</span>
                <div class="flex items-start gap-2.5 font-body-dense text-body-dense text-on-surface-variant">
                    <span class="material-symbols-outlined text-[18px] shrink-0 text-on-surface">location_on</span>
                    <span>Kampus SMK Plus Pelita Nusantara, Jl. Golf No. 1, Ciriung, Cibinong, Kab. Bogor, Jawa Barat 16918</span>
                </div>
                <div class="flex items-center gap-2.5 font-body-dense text-body-dense text-on-surface-variant">
                    <span class="material-symbols-outlined text-[18px] shrink-0 text-on-surface">call</span>
                    <span>(021) 875-4321 / WhatsApp BKK</span>
                </div>
                <div class="flex items-center gap-2.5 font-body-dense text-body-dense text-on-surface-variant">
                    <span class="material-symbols-outlined text-[18px] shrink-0 text-on-surface">mail</span>
                    <span>bkk@smkpenus.sch.id</span>
                </div>
            </div>
        </div>

        <!-- Copyright & Legal -->
        <div class="mt-12 pt-8 border-t border-surface-container flex flex-col sm:flex-row items-center justify-between gap-4 font-body-dense text-body-dense text-on-surface-variant">
            <p>© {{ date('Y') }} BKK SMK Plus Pelita Nusantara. Hak Cipta Dilindungi Undang-Undang.</p>
            <div class="flex items-center gap-6 font-label-dense text-label-dense">
                <span>Kemendikbudristek Vokasi</span>
                <span>Disnaker Kab. Bogor</span>
                <a class="hover:text-on-surface transition-colors" href="#">Kebijakan Privasi</a>
            </div>
        </div>
    </div>
</footer>
