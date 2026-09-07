<dialog id="backup-delivery-dialog" class="backup-delivery-dialog" dir="rtl" aria-labelledby="backup-delivery-title">
    <div class="backup-delivery-dialog__head">
        <div>
            <span class="backup-eyebrow">روش دریافت پشتیبان</span>
            <h2 id="backup-delivery-title">پشتیبان را کجا نگه داریم؟</h2>
        </div>
        <button type="button" class="backup-delivery-dialog__close" onclick="closeBackupDeliveryPrompt()" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <p>می‌توانی فایل را برای آرشیو روی سرور نگه داری یا همین حالا مستقیماً دانلود کنی.</p>
    <div class="backup-delivery-dialog__actions">
        <button type="button" class="backup-button backup-button--secondary" onclick="submitBackupDelivery('server')"><i class="fa-solid fa-server"></i><span><b>ذخیره روی سرور</b><small>در فهرست پشتیبان‌ها می‌ماند</small></span></button>
        <button type="button" class="backup-button backup-button--primary" onclick="submitBackupDelivery('download')"><i class="fa-solid fa-download"></i><span><b>دانلود مستقیم</b><small>پس از ارسال از سرور پاک می‌شود</small></span></button>
    </div>
</dialog>

<script>
    (function () {
        var activeForm = null;
        window.openBackupDeliveryPrompt = function (form) {
            if (!form) return;
            activeForm = form;
            var dialog = document.getElementById('backup-delivery-dialog');
            if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
        };
        window.closeBackupDeliveryPrompt = function () {
            var dialog = document.getElementById('backup-delivery-dialog');
            if (dialog?.open) dialog.close();
            activeForm = null;
        };
        window.submitBackupDelivery = function (delivery) {
            if (!activeForm) return;
            var old = activeForm.querySelector('input[name="delivery"]');
            if (old) old.remove();
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delivery';
            input.value = delivery;
            activeForm.appendChild(input);
            var form = activeForm;
            closeBackupDeliveryPrompt();
            HTMLFormElement.prototype.submit.call(form);
        };
    })();
</script>
