<?php
$shortcuts = $shortcuts ?? $keyboardShortcuts ?? [];
$actionTypes = $actionTypes ?? $keyboardActionTypes ?? [];
$keyboardRedirect = $keyboardRedirect ?? '';
$keyboardHeading = $keyboardHeading ?? 'كيبورد CNR Cashier';
$keyboardSubheading = $keyboardSubheading ?? 'تخصيص أزرار الكيبورد مع وظائف شاشة البيع.';
$redirectField = $keyboardRedirect !== ''
    ? '<input type="hidden" name="_redirect" value="' . e($keyboardRedirect) . '">'
    : '';
?>

<section id="cashier-keyboard" class="keyboard-settings">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h6 class="mb-1"><?= e($keyboardHeading) ?></h6>
            <div class="form-text"><?= e($keyboardSubheading) ?></div>
        </div>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addShortcutModal">
            إضافة زر
        </button>
    </div>

    <?php if (empty($shortcuts)): ?>
        <div class="alert alert-info mb-3">
            لا توجد أزرار مخصصة حتى الآن. استخدم "إضافة زر" ثم اضغط الزر المطلوب من كيبورد CNR لتسجيله.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>زر الكيبورد</th>
                        <th>التسمية</th>
                        <th>وظيفة البرنامج</th>
                        <th>المرجع</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shortcuts as $shortcut): ?>
                        <tr>
                            <td><code class="bg-light p-2 rounded"><?= e($shortcut['key_code']) ?></code></td>
                            <td><?= e($shortcut['key_label']) ?></td>
                            <td><span class="badge bg-info"><?= e($actionTypes[$shortcut['action_type']] ?? $shortcut['action_type']) ?></span></td>
                            <td>
                                <?php if ($shortcut['reference_name']): ?>
                                    <small class="text-muted"><?= e($shortcut['reference_name']) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($shortcut['is_active']): ?>
                                    <span class="badge bg-success">مفعل</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">معطل</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-warning edit-shortcut" data-id="<?= (int) $shortcut['id'] ?>" data-bs-toggle="modal" data-bs-target="#editShortcutModal">
                                        تعديل
                                    </button>
                                    <form method="post" action="<?= url('/cashier-keyboard/' . (int) $shortcut['id'] . '/toggle') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <?= $redirectField ?>
                                        <button type="submit" class="btn btn-outline-info">تفعيل/تعطيل</button>
                                    </form>
                                    <form method="post" action="<?= url('/cashier-keyboard/' . (int) $shortcut['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('هل تريد حذف هذا الزر؟');">
                                        <?= csrf_field() ?>
                                        <?= $redirectField ?>
                                        <button type="submit" class="btn btn-outline-danger">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="addShortcutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة زر CNR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= url('/cashier-keyboard') ?>">
                <?= csrf_field() ?>
                <?= $redirectField ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">زر الكيبورد *</label>
                        <div class="input-group">
                            <input type="text" name="key_code" class="form-control" data-key-capture="add" placeholder="مثال: F2 أو Ctrl+P" required>
                            <button class="btn btn-outline-primary" type="button" data-capture-target="add">التقاط</button>
                        </div>
                        <small class="text-muted">اضغط "التقاط" ثم اضغط الزر المبرمج على كيبورد CNR.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم الزر *</label>
                        <input type="text" name="key_label" class="form-control" data-key-label="add" placeholder="مثال: طباعة الإيصال" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وظيفة البرنامج *</label>
                        <select name="action_type" class="form-select" required>
                            <option value="">اختر وظيفة</option>
                            <?php foreach ($actionTypes as $code => $label): ?>
                                <option value="<?= e($code) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الصنف أو المرجع</label>
                        <input type="number" name="reference_id" class="form-control" placeholder="يستخدم مع وظيفة إضافة منتج">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم المرجع</label>
                        <input type="text" name="reference_name" class="form-control" placeholder="مثال: صنف سريع أو خصم">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" checked id="addIsActive">
                        <label class="form-check-label" for="addIsActive">مفعل</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الزر</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editShortcutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل زر CNR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="editShortcutForm">
                <?= csrf_field() ?>
                <?= $redirectField ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">زر الكيبورد *</label>
                        <div class="input-group">
                            <input type="text" name="key_code" class="form-control" id="editKeyCode" data-key-capture="edit" required>
                            <button class="btn btn-outline-primary" type="button" data-capture-target="edit">التقاط</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم الزر *</label>
                        <input type="text" name="key_label" class="form-control" id="editKeyLabel" data-key-label="edit" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وظيفة البرنامج *</label>
                        <select name="action_type" class="form-select" id="editActionType" required>
                            <option value="">اختر وظيفة</option>
                            <?php foreach ($actionTypes as $code => $label): ?>
                                <option value="<?= e($code) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الصنف أو المرجع</label>
                        <input type="number" name="reference_id" class="form-control" id="editReferenceId">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم المرجع</label>
                        <input type="text" name="reference_name" class="form-control" id="editReferenceName">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="editIsActive">
                        <label class="form-check-label" for="editIsActive">مفعل</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const shortcuts = <?= json_encode(array_values($shortcuts), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    function keyNameFromEvent(event) {
        if (['Control', 'Shift', 'Alt', 'Meta'].includes(event.key)) {
            return '';
        }

        if (/^Key[A-Z]$/.test(event.code || '')) {
            return event.code.slice(3);
        }

        if (/^Digit[0-9]$/.test(event.code || '')) {
            return event.code.slice(5);
        }

        if (/^Numpad/.test(event.code || '')) {
            return event.code.replace('Numpad', 'Num');
        }

        const aliases = {
            ' ': 'Space',
            ArrowUp: 'ArrowUp',
            ArrowDown: 'ArrowDown',
            ArrowLeft: 'ArrowLeft',
            ArrowRight: 'ArrowRight',
            Escape: 'Esc',
        };

        return aliases[event.key] || event.key || event.code || '';
    }

    function shortcutFromEvent(event) {
        const key = keyNameFromEvent(event);
        if (!key) {
            return '';
        }

        const parts = [];
        if (event.ctrlKey || event.metaKey) parts.push('Ctrl');
        if (event.altKey) parts.push('Alt');
        if (event.shiftKey) parts.push('Shift');
        parts.push(key.length === 1 ? key.toUpperCase() : key);
        return parts.join('+');
    }

    document.querySelectorAll('[data-capture-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetName = button.dataset.captureTarget;
            const input = document.querySelector(`[data-key-capture="${targetName}"]`);
            const label = document.querySelector(`[data-key-label="${targetName}"]`);
            if (!input) return;

            const originalText = button.textContent;
            button.textContent = 'اضغط الزر الآن';
            button.classList.add('active');
            input.focus();

            const capture = (event) => {
                event.preventDefault();
                event.stopPropagation();

                const shortcut = shortcutFromEvent(event);
                if (shortcut) {
                    input.value = shortcut;
                    if (label && !label.value.trim()) {
                        label.value = 'زر ' + shortcut;
                    }
                }

                button.textContent = originalText;
                button.classList.remove('active');
                window.removeEventListener('keydown', capture, true);
            };

            window.addEventListener('keydown', capture, true);
        });
    });

    document.querySelectorAll('.edit-shortcut').forEach((button) => {
        button.addEventListener('click', () => {
            const shortcut = shortcuts.find((item) => String(item.id) === String(button.dataset.id));
            if (!shortcut) return;

            document.getElementById('editKeyCode').value = shortcut.key_code;
            document.getElementById('editKeyLabel').value = shortcut.key_label;
            document.getElementById('editActionType').value = shortcut.action_type;
            document.getElementById('editReferenceId').value = shortcut.reference_id || '';
            document.getElementById('editReferenceName').value = shortcut.reference_name || '';
            document.getElementById('editIsActive').checked = Number(shortcut.is_active) === 1;
            document.getElementById('editShortcutForm').action = '<?= url('/cashier-keyboard/') ?>' + shortcut.id + '/update';
        });
    });
})();
</script>
