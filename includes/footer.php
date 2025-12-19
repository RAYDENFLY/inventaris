    </div>

    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div id="flashMessage" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?php echo $_SESSION['flash_message']; ?></span>
                <button onclick="closeFlashMessage()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <script>
            function closeFlashMessage() {
                document.getElementById('flashMessage').style.display = 'none';
            }
            setTimeout(closeFlashMessage, 5000);
        </script>
        <?php unset($_SESSION['flash_message']); ?>

        <script>
            // User menu dropdown
            document.getElementById('userMenuBtn').addEventListener('click', function() {
                var menu = document.getElementById('userMenu');
                menu.classList.toggle('hidden');
            });
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                var menu = document.getElementById('userMenu');
                var btn = document.getElementById('userMenuBtn');
                if (!btn.contains(event.target) && !menu.contains(event.target)) {
                    menu.classList.add('hidden');
                }
            });
        </script>
    <?php endif; ?>

    <!-- Team trigger in footer (bg-blue-600) -->
    <footer class="mt-6">
        <div class="max-w-4xl mx-auto px-4 py-4 bg-blue-600 text-white rounded-lg flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-users text-2xl"></i>
                <div>
                    <div class="font-semibold">kelompok 2</div>
                    <div class="text-sm text-blue-100">Fahat Fajar Andhika · Pirman Hermawan · Azis Maulana Suhada</div>
                </div>
            </div>
            <button id="openTeamBtn" class="bg-white text-blue-600 px-3 py-2 rounded hover:bg-gray-100">Lihat Tim</button>
        </div>
    </footer>

    <!-- Team Modal -->
    <div id="teamModal" class="fixed inset-0 z-50 hidden items-center justify-center">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" data-close></div>
        <div role="dialog" aria-modal="true" aria-labelledby="teamModalLabel" class="relative max-w-2xl w-full bg-white rounded-xl shadow-xl p-6 mx-4 transform transition-all duration-200">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-blue-50 rounded-md">
                        <i class="fas fa-layer-group w-7 h-7 text-blue-600"></i>
                    </div>
                    <div>
                        <h2 id="teamModalLabel" class="text-xl font-semibold text-gray-900">kelompok 2</h2>
                        <p class="text-sm text-gray-600">Kelompok pengembang yang membuat aplikasi inventaris ini.</p>
                    </div>
                </div>
                <button id="closeTeamBtnTop" aria-label="Tutup" class="p-2 rounded hover:bg-gray-100">
                    <i class="fas fa-times text-gray-700"></i>
                </button>
            </div>

            <div class="mb-4">
                <h3 class="font-medium text-sm text-gray-800 mb-2">Teknologi & Integrasi</h3>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-2 text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded"><i class="fas fa-code w-3 h-3"></i> PHP</span>
                    <span class="inline-flex items-center gap-2 text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">Tailwind CSS</span>
                    <span class="inline-flex items-center gap-2 text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">FontAwesome</span>
                </div>
            </div>

            <div>
                <h3 class="font-medium text-sm text-gray-800 mb-2">Anggota</h3>
                <ul class="space-y-3">
                    <li class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center">
                                <i class="fas fa-users text-blue-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Azis Maulana Suhada</div>
                                <div class="text-xs text-gray-600">Pengembang</div>
                            </div>
                        </div>
                        <a href="#" class="inline-flex items-center gap-2 px-3 py-1 bg-gray-100 rounded text-sm text-gray-700 hover:bg-gray-200">
                            <i class="fab fa-github"></i>
                            <span>GitHub</span>
                        </a>
                    </li>

                    <li class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center">
                                <i class="fas fa-users text-gray-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Fahat Fajar Andhika</div>
                                <div class="text-xs text-gray-600">Desain & Support</div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600">&nbsp;</div>
                    </li>

                    <li class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center">
                                <i class="fas fa-users text-gray-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Pirman Hermawan</div>
                                <div class="text-xs text-gray-600">Kontributor</div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600">&nbsp;</div>
                    </li>
                </ul>
            </div>

            <div class="mt-6 text-right">
                <button id="closeTeamBtn" class="px-4 py-2 bg-blue-600 text-white rounded">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        (function(){
            var openBtn = document.getElementById('openTeamBtn');
            var modal = document.getElementById('teamModal');
            var closeBtn = document.getElementById('closeTeamBtn');
            var closeBtnTop = document.getElementById('closeTeamBtnTop');
            var overlay = modal ? modal.querySelector('[data-close]') : null;

            function openTeamModal(){ if(!modal) return; modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden'; }
            function closeTeamModal(){ if(!modal) return; modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow = ''; }

            if(openBtn) openBtn.addEventListener('click', openTeamModal);
            if(closeBtn) closeBtn.addEventListener('click', closeTeamModal);
            if(closeBtnTop) closeBtnTop.addEventListener('click', closeTeamModal);
            if(overlay) overlay.addEventListener('click', closeTeamModal);
            document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeTeamModal(); });
        })();
    </script>

    <script>
        // Confirm delete
        function confirmDelete(message) {
            return confirm(message || 'Apakah Anda yakin ingin menghapus data ini?');
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Open modal
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        // User menu dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userMenu = document.getElementById('userMenu');

            if (userMenuBtn && userMenu) {
                userMenuBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('hidden');
                });

                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    if (!userMenuBtn.contains(event.target) && !userMenu.contains(event.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>
</html>