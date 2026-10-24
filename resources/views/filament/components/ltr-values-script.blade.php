{{-- In right-to-left pages, phone numbers, emails, URLs and codes (INV/2026/0001) lose their order and
     punctuation. Mark elements that contain only such a value so each one is laid out on its own. --}}
<script>
    (() => {
        const patterns = [
            /^\+?\d[\d\s\-()]{6,}$/,
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            /^https?:\/\/\S+$/i,
            /^[A-Za-z0-9]+(?:[\/\-_.][A-Za-z0-9]+)+$/,
        ];

        const skip = new Set(['SCRIPT', 'STYLE', 'INPUT', 'TEXTAREA', 'SELECT', 'OPTION', 'CODE', 'PRE']);

        const mark = (root) => {
            if (document.documentElement.dir !== 'rtl') {
                return;
            }

            const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);

            for (let node = walker.nextNode(); node; node = walker.nextNode()) {
                const text = node.textContent.trim();
                const parent = node.parentElement;

                if (text.length < 5 || text.length > 120 || !parent || skip.has(parent.tagName) || parent.dataset.ltrValue) {
                    continue;
                }

                if (parent.textContent.trim() === text && patterns.some((pattern) => pattern.test(text))) {
                    parent.style.unicodeBidi = 'plaintext';
                    parent.dataset.ltrValue = '1';
                }
            }
        };

        let queued = false;

        const schedule = () => {
            if (queued) {
                return;
            }

            queued = true;

            requestAnimationFrame(() => {
                queued = false;
                mark(document.body);
            });
        };

        document.addEventListener('DOMContentLoaded', schedule);
        document.addEventListener('livewire:navigated', schedule);
        new MutationObserver(schedule).observe(document.documentElement, { childList: true, subtree: true, characterData: true });
        schedule();
    })();
</script>
