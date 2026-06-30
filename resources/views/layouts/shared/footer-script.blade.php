<!-- bundle -->
@yield('script')
<!-- App js -->
@yield('script-bottom')

<script>
    // Prevent double form submissions sitewide
    document.addEventListener('submit', function (e) {
        const form = e.target;
        
        // If form is already submitting, prevent duplicate submission
        if (form.classList.contains('form-submitting')) {
            e.preventDefault();
            return;
        }
        
        form.classList.add('form-submitting');
        
        // Find the submit button
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        
        if (submitBtn) {
            // Use setTimeout to allow other scripts to process the submit event first
            setTimeout(() => {
                // If another script prevented the submission, re-enable the form
                if (e.defaultPrevented) {
                    form.classList.remove('form-submitting');
                    return;
                }
                
                // Visually disable the button to prevent multiple clicks
                submitBtn.disabled = true;
                if (submitBtn.tagName.toLowerCase() === 'button') {
                    if (!submitBtn.innerHTML.includes('spinner-border')) {
                        submitBtn.dataset.originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';
                    }
                } else if (submitBtn.tagName.toLowerCase() === 'input') {
                    submitBtn.dataset.originalValue = submitBtn.value;
                    submitBtn.value = 'Processing...';
                }
            }, 10);
        }
    });
</script>
