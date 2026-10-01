{{-- WhatsApp Customer Share Modal Partial --}}
<div class="modal fade" id="whatsappShareModal" tabindex="-1" aria-labelledby="whatsappShareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header text-white" style="background-color: #25D366;">
                <h5 class="modal-title d-flex align-items-center gap-2 fw-bold" id="whatsappShareModalLabel">
                    <i class="ri-whatsapp-fill fs-4"></i> Share via WhatsApp
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Customer Mini Header -->
                <div class="d-flex align-items-center justify-content-between p-2 mb-3 bg-light rounded border">
                    <div>
                        <div class="fw-bold fs-5 text-dark" id="waCustomerName">—</div>
                        <div class="text-muted small" id="waCustomerMeta">—</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-soft-success text-success border border-success px-2 py-1" id="waCustomerType">Customer</span>
                        <a id="waPdfDownloadBtn" href="#" target="_blank" class="btn btn-danger btn-sm d-none" title="Open / Download Statement PDF">
                            <i class="ri-file-pdf-line me-1"></i> View PDF
                        </a>
                    </div>
                </div>

                <!-- Template Selector -->
                <label class="form-label fw-semibold text-muted small mb-2 d-flex justify-content-between">
                    <span>SELECT MESSAGE TEMPLATE:</span>
                    <span class="text-muted" style="font-size: 11px;">Click to switch template</span>
                </label>
                <div class="btn-group w-100 mb-3" role="group" id="waTemplateButtons">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-template="statement" id="waTemplateStatementBtn">
                        <i class="ri-file-list-3-line me-1"></i> Statement (PDF Link)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm active" data-template="profile" id="waTemplateProfileBtn">
                        <i class="ri-file-user-line me-1"></i> Full Info
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-template="due" id="waTemplateDueBtn">
                        <i class="ri-money-dollar-circle-line me-1"></i> Due Notice
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-template="delivery" id="waTemplateDeliveryBtn">
                        <i class="ri-truck-line me-1"></i> Address Only
                    </button>
                </div>

                <!-- Editable Message Box -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold small text-muted mb-0">MESSAGE PREVIEW (EDITABLE):</label>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary" id="waResetBtn" title="Reset to original template text">
                            <i class="ri-restart-line"></i> Reset
                        </button>
                    </div>
                    <textarea class="form-control font-monospace" id="waMessageText" rows="11" style="font-size: 13px; line-height: 1.45;"></textarea>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    <i class="ri-information-line"></i> Text is fully editable. You can customize notes, payment reminders, or dates before sending.
                </div>
            </div>

            <div class="modal-footer bg-light p-2 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="waCopyBtn">
                    <i class="ri-file-copy-line me-1"></i> <span id="waCopyBtnText">Copy Text</span>
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-success btn-sm" id="waSendDirectBtn" title="Send directly to customer's WhatsApp">
                        <i class="ri-send-plane-fill me-1"></i> <span id="waSendDirectText">Send to Customer</span>
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="waShareAnyBtn" style="background-color: #25D366; border-color: #25D366;" title="Share to any WhatsApp contact, team member, or group">
                        <i class="ri-whatsapp-line me-1"></i> Share on WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let currentWaCustomer = null;
    let currentTemplate = 'profile';

    function normalizeBDPhone(phone) {
        if (!phone) return '';
        let cleaned = phone.replace(/[^0-9]/g, '');
        if (cleaned.startsWith('880')) return cleaned;
        if (cleaned.startsWith('0')) return '88' + cleaned;
        if (cleaned.length === 10) return '880' + cleaned;
        return cleaned;
    }

    function formatNumber(num) {
        return (parseFloat(num) || 0).toLocaleString('en-US');
    }

    function formatTypeLabel(type) {
        if (!type) return 'Customer';
        if (type === 'special_dealer') return 'Special Dealer';
        if (type === 'dealer') return 'Authorized Dealer';
        return type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ');
    }

    function generateMessage(customer, templateType) {
        if (!customer) return '';
        
        const companyName = 'Radhikas Trade International';
        const name = customer.name || 'Valued Customer';
        const company = customer.company ? `🏬 *Company:* ${customer.company}\n` : '';
        const typeLabel = formatTypeLabel(customer.customer_type);
        const phone = customer.phone || '—';
        const district = customer.district ? `📍 *District:* ${customer.district}\n` : '';
        const address = customer.address ? `🏠 *Address:* ${customer.address}\n` : '';
        const dueVal = parseFloat(customer.total_due) || 0;
        const creditLimitVal = parseFloat(customer.credit_limit) || 0;
        const walletVal = parseFloat(customer.wallet_balance) || 0;
        const showUrl = customer.show_url || '';
        const pdfUrl = customer.statement_pdf_url || '';

        // Statement Template
        if (templateType === 'statement') {
            let periodStr = 'All Time History';
            if (customer.start_date && customer.end_date) {
                periodStr = `${customer.start_date} to ${customer.end_date}`;
            } else if (customer.start_date) {
                periodStr = `From ${customer.start_date}`;
            } else if (customer.end_date) {
                periodStr = `Up to ${customer.end_date}`;
            }

            const openBal = parseFloat(customer.opening_balance) || 0;
            const pDebit = parseFloat(customer.period_debit) || 0;
            const pCredit = parseFloat(customer.period_credit) || 0;
            const closeBal = parseFloat(customer.closing_balance !== undefined ? customer.closing_balance : customer.total_due) || 0;

            let msg = `🏢 *${companyName}*\n` +
                      `📄 *Customer Account Statement*\n` +
                      `━━━━━━━━━━━━━━━━━━━━\n` +
                      `👤 *Customer:* ${name}\n` +
                      company +
                      `🏷️ *Type:* ${typeLabel}\n` +
                      `📞 *Phone:* ${phone}\n` +
                      `📅 *Period:* ${periodStr}\n` +
                      `━━━━━━━━━━━━━━━━━━━━\n` +
                      `💵 *Opening Balance:* ৳${formatNumber(openBal)}\n` +
                      `📈 *Period Purchases (+):* ৳${formatNumber(pDebit)}\n` +
                      `📉 *Period Payments (-):* ৳${formatNumber(pCredit)}\n` +
                      `💰 *Closing Due Balance:* ৳${formatNumber(closeBal)}\n` +
                      `━━━━━━━━━━━━━━━━━━━━\n`;

            if (pdfUrl) {
                msg += `📥 *Download Statement PDF:*\n${pdfUrl}\n\n`;
            }

            msg += `Kindly review your statement. Please contact our accounts team for any query.\nThank you for your valued business!`;
            return msg;
        }

        if (templateType === 'delivery') {
            return `🏢 *${companyName}*\n` +
                   `📦 *Customer Contact & Delivery Info*\n` +
                   `━━━━━━━━━━━━━━━━━━━━\n` +
                   `📛 *Customer:* ${name}\n` +
                   company +
                   `🏷️ *Type:* ${typeLabel}\n` +
                   `📞 *Phone:* ${phone}\n` +
                   district +
                   address +
                   `━━━━━━━━━━━━━━━━━━━━`;
        }

        if (templateType === 'due') {
            let msg = `🏢 *${companyName}*\n\n` +
                      `Dear *${name}*,\n` +
                      `Greetings from ${companyName}.\n` +
                      `Here is your current account balance update:\n\n` +
                      `💰 *Total Outstanding Due:* ৳${formatNumber(dueVal)}\n`;
            if (creditLimitVal > 0) {
                msg += `💳 *Credit Limit:* ৳${formatNumber(creditLimitVal)}\n`;
            }
            if (walletVal > 0) {
                msg += `💼 *Wallet Balance:* ৳${formatNumber(walletVal)}\n`;
            }
            if (pdfUrl) {
                msg += `\n📄 *Statement PDF:* ${pdfUrl}\n`;
            }
            msg += `\nKindly arrange the due payment at your earliest convenience. If you have already paid, please ignore this notice.\n\n` +
                   `Thank you for your cooperation!`;
            return msg;
        }

        // Default: 'profile' (Full info)
        let profileMsg = `🏢 *${companyName}*\n` +
                         `👤 *Customer Information*\n` +
                         `━━━━━━━━━━━━━━━━━━━━\n` +
                         `📛 *Name:* ${name}\n` +
                         company +
                         `🏷️ *Type:* ${typeLabel}\n` +
                         `📞 *Phone:* ${phone}\n` +
                         (customer.email ? `✉️ *Email:* ${customer.email}\n` : '') +
                         district +
                         address +
                         `💰 *Total Due:* ৳${formatNumber(dueVal)}\n`;
        if (creditLimitVal > 0) {
            profileMsg += `💳 *Credit Limit:* ৳${formatNumber(creditLimitVal)}\n`;
        }
        if (walletVal > 0) {
            profileMsg += `💼 *Wallet Balance:* ৳${formatNumber(walletVal)}\n`;
        }
        if (pdfUrl) {
            profileMsg += `📄 *Statement PDF:* ${pdfUrl}\n`;
        }
        if (showUrl) {
            profileMsg += `━━━━━━━━━━━━━━━━━━━━\n🔗 *ERP Profile:* ${showUrl}`;
        } else {
            profileMsg += `━━━━━━━━━━━━━━━━━━━━`;
        }
        return profileMsg;
    }

    function updateModalContent() {
        if (!currentWaCustomer) return;

        document.getElementById('waCustomerName').textContent = currentWaCustomer.name || 'Customer';
        
        let meta = [];
        if (currentWaCustomer.company) meta.push(currentWaCustomer.company);
        if (currentWaCustomer.phone) meta.push(currentWaCustomer.phone);
        if (currentWaCustomer.district) meta.push(currentWaCustomer.district);
        document.getElementById('waCustomerMeta').textContent = meta.join(' • ') || 'No extra info';

        document.getElementById('waCustomerType').textContent = formatTypeLabel(currentWaCustomer.customer_type);

        // PDF Button
        const pdfBtn = document.getElementById('waPdfDownloadBtn');
        if (currentWaCustomer.statement_pdf_url) {
            pdfBtn.href = currentWaCustomer.statement_pdf_url;
            pdfBtn.classList.remove('d-none');
        } else {
            pdfBtn.classList.add('d-none');
        }

        // Update Direct Send Button state
        const directBtn = document.getElementById('waSendDirectBtn');
        const directText = document.getElementById('waSendDirectText');
        const normPhone = normalizeBDPhone(currentWaCustomer.phone);

        if (normPhone) {
            directBtn.disabled = false;
            directBtn.classList.remove('disabled');
            directText.textContent = `Send to ${currentWaCustomer.phone}`;
        } else {
            directBtn.disabled = true;
            directBtn.classList.add('disabled');
            directText.textContent = 'No Phone Available';
        }

        // Update message text
        document.getElementById('waMessageText').value = generateMessage(currentWaCustomer, currentTemplate);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Delegated click listener for regular .btn-whatsapp-share and .btn-whatsapp-statement
        document.addEventListener('click', function(e) {
            const statementBtn = e.target.closest('.btn-whatsapp-statement');
            const shareBtn = e.target.closest('.btn-whatsapp-share');

            if (!statementBtn && !shareBtn) return;

            e.preventDefault();
            const targetBtn = statementBtn || shareBtn;
            const dataStr = targetBtn.getAttribute('data-customer');
            if (!dataStr) return;

            try {
                currentWaCustomer = typeof dataStr === 'string' ? JSON.parse(dataStr) : dataStr;
                currentTemplate = statementBtn ? 'statement' : (currentWaCustomer.statement_pdf_url ? 'statement' : 'profile');

                // Set template buttons active state
                document.querySelectorAll('#waTemplateButtons button').forEach(b => {
                    b.classList.toggle('active', b.getAttribute('data-template') === currentTemplate);
                });

                updateModalContent();

                const modalEl = document.getElementById('whatsappShareModal');
                if (modalEl && window.bootstrap) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            } catch (err) {
                console.error('Error opening WhatsApp modal:', err);
            }
        });

        // Template button switching
        document.querySelectorAll('#waTemplateButtons button').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('#waTemplateButtons button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentTemplate = this.getAttribute('data-template');
                if (currentWaCustomer) {
                    document.getElementById('waMessageText').value = generateMessage(currentWaCustomer, currentTemplate);
                }
            });
        });

        // Reset button
        const resetBtn = document.getElementById('waResetBtn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                if (currentWaCustomer) {
                    document.getElementById('waMessageText').value = generateMessage(currentWaCustomer, currentTemplate);
                }
            });
        }

        // Copy text button
        const copyBtn = document.getElementById('waCopyBtn');
        const copyBtnText = document.getElementById('waCopyBtnText');
        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                const text = document.getElementById('waMessageText').value;
                if (!text) return;

                navigator.clipboard.writeText(text).then(function() {
                    const originalText = copyBtnText.textContent;
                    copyBtnText.textContent = 'Copied!';
                    copyBtn.classList.replace('btn-outline-secondary', 'btn-secondary');
                    setTimeout(function() {
                        copyBtnText.textContent = originalText;
                        copyBtn.classList.replace('btn-secondary', 'btn-outline-secondary');
                    }, 2000);
                }).catch(function() {
                    const textarea = document.getElementById('waMessageText');
                    textarea.select();
                    document.execCommand('copy');
                    copyBtnText.textContent = 'Copied!';
                    setTimeout(() => { copyBtnText.textContent = 'Copy Text'; }, 2000);
                });
            });
        }

        // Send Direct to Customer (wa.me)
        const sendDirectBtn = document.getElementById('waSendDirectBtn');
        if (sendDirectBtn) {
            sendDirectBtn.addEventListener('click', function() {
                if (!currentWaCustomer || !currentWaCustomer.phone) return;
                const normPhone = normalizeBDPhone(currentWaCustomer.phone);
                if (!normPhone) return;

                const text = document.getElementById('waMessageText').value;
                const url = `https://wa.me/${normPhone}?text=${encodeURIComponent(text)}`;
                window.open(url, '_blank');
            });
        }

        // Share to Any Contact or Group (api.whatsapp.com/send)
        const shareAnyBtn = document.getElementById('waShareAnyBtn');
        if (shareAnyBtn) {
            shareAnyBtn.addEventListener('click', function() {
                const text = document.getElementById('waMessageText').value;
                const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`;
                window.open(url, '_blank');
            });
        }
    });
})();
</script>
