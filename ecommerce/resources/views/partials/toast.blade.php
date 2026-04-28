{{-- IMP-009: Global toast notification system --}}
@once
    <style>
        .imp009-toast-area {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: .45rem;
            width: min(92vw, 320px);
            pointer-events: none;
        }

        .imp009-toast {
            padding: .65rem .95rem;
            border-radius: .45rem;
            font-size: .875rem;
            font-family: sans-serif;
            line-height: 1.35;
            box-shadow: 0 8px 18px rgba(0, 0, 0, .18);
            border: 1px solid transparent;
            color: #fff;
            opacity: 0;
            transform: translateY(-8px);
            transition: opacity .22s ease, transform .22s ease;
            pointer-events: auto;
        }

        .imp009-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .imp009-toast--success {
            background: #198754;
            border-color: #157347;
        }

        .imp009-toast--error {
            background: #dc3545;
            border-color: #bb2d3b;
        }

        .imp009-toast--info {
            background: #0d6efd;
            border-color: #0b5ed7;
        }
    </style>

    <script>
        window.imp009ToastState = window.imp009ToastState || {
            ready: false,
            queue: [],
            seed: 0,
        };

        window.imp009EnqueueToast = function (detail) {
            if (!detail || !detail.msg) {
                return;
            }

            const payload = {
                id: detail.id || ('imp009-' + Date.now() + '-' + (++window.imp009ToastState.seed)),
                type: detail.type || 'info',
                msg: String(detail.msg),
            };

            if (!window.imp009ToastState.ready) {
                window.imp009ToastState.queue.push(payload);
                return;
            }

            window.dispatchEvent(new CustomEvent('imp009-toast', { detail: payload }));
        };

        document.addEventListener('DOMContentLoaded', function () {
            const area = document.getElementById('imp009-toast-area');
            if (!area) {
                return;
            }

            window.imp009ToastState.ready = true;

            window.addEventListener('imp009-toast', function (event) {
                const detail = event.detail || {};
                if (!detail.msg) {
                    return;
                }

                const toast = document.createElement('div');
                toast.className = 'imp009-toast imp009-toast--' + (detail.type || 'info');
                toast.setAttribute('role', 'alert');
                toast.textContent = detail.msg;
                area.appendChild(toast);

                requestAnimationFrame(function () {
                    toast.classList.add('is-visible');
                });

                setTimeout(function () {
                    toast.classList.remove('is-visible');
                    setTimeout(function () {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 220);
                }, 3200);
            });

            while (window.imp009ToastState.queue.length > 0) {
                const queued = window.imp009ToastState.queue.shift();
                window.dispatchEvent(new CustomEvent('imp009-toast', { detail: queued }));
            }
        });
    </script>
@endonce

<div id="imp009-toast-area" class="imp009-toast-area" aria-live="polite" aria-atomic="true"></div>

@if (session('success'))
    <script>
        window.imp009EnqueueToast({ type: 'success', msg: @json(session('success')) });
    </script>
@endif

@if (session('error'))
    <script>
        window.imp009EnqueueToast({ type: 'error', msg: @json(session('error')) });
    </script>
@endif

@if (session('status'))
    <script>
        window.imp009EnqueueToast({ type: 'info', msg: @json(session('status')) });
    </script>
@endif