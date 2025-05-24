
(function() {
    'use strict';
    
    // Navigation functions
    window.dmrNextStep = function(step) {
        if (validateCurrentStep()) {
            updateStepInForm(step);
            navigateToStep(step);
        }
    };
    
    window.dmrPrevStep = function(step) {
        updateStepInForm(step);
        navigateToStep(step);
    };
    
    function validateCurrentStep() {
        var currentStep = parseInt(document.getElementById('dmr_current_step').value);
        
        if (currentStep === 1) {
            // Validate all questions are answered
            var questions = document.querySelectorAll('.dmr-question');
            for (var i = 0; i < questions.length; i++) {
                var radios = questions[i].querySelectorAll('input[type="radio"]');
                var answered = false;
                for (var j = 0; j < radios.length; j++) {
                    if (radios[j].checked) {
                        answered = true;
                        break;
                    }
                }
                if (!answered) {
                    alert('Please answer all questions before proceeding.');
                    return false;
                }
            }
            return true;
        }
        
        if (currentStep === 2) {
            // Validate required fields
            var form = document.getElementById('dmr-form');
            var requiredFields = form.querySelectorAll('[required]');
            for (var i = 0; i < requiredFields.length; i++) {
                if (!requiredFields[i].value.trim()) {
                    alert('Please fill in all required fields.');
                    requiredFields[i].focus();
                    return false;
                }
            }
            
            // Build review content before navigating
            buildReviewContent();
            return true;
        }
        
        if (currentStep === 3) {
            // Build review content when loading step 3
            buildReviewContent();
        }
        
        return true;
    }
    
    function updateStepInForm(step) {
        document.getElementById('dmr_current_step').value = step;
    }
    
    function navigateToStep(step) {
        if (typeof dmrAjax === 'undefined') {
            // Fallback to page reload if AJAX not available
            var url = new URL(window.location.href);
            url.searchParams.set('dmr_step', step);
            window.location.href = url.toString();
            return;
        }
        
        // Show loading state
        var form = document.getElementById('dmr-form');
        var stepContent = form.querySelector('.dmr-step-content');
        if (stepContent) {
            stepContent.style.opacity = '0.5';
            stepContent.style.pointerEvents = 'none';
        }
        
        // Make AJAX request
        jQuery.ajax({
            url: dmrAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dmr_get_step',
                step: step,
                nonce: dmrAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update step content with fade effect
                    if (stepContent) {
                        stepContent.style.transition = 'opacity 0.3s ease';
                        setTimeout(function() {
                            stepContent.innerHTML = response.data.content;
                            stepContent.style.opacity = '1';
                            stepContent.style.pointerEvents = 'auto';
                        }, 150);
                    }
                    
                    // Update hidden step field
                    var stepField = document.getElementById('dmr_current_step');
                    if (stepField) {
                        stepField.value = response.data.step;
                    }
                    
                    // Update progress indicators
                    updateProgressIndicators(response.data.step);
                    
                    // Re-initialize radio button styling
                    setTimeout(function() {
                        initRadioButtonStyling();
                        
                        // If step 3, build review content
                        if (response.data.step === 3) {
                            buildReviewContent();
                        }
                    }, 100);
                    
                    // Scroll to top of form
                    setTimeout(function() {
                        if (form) {
                            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 200);
                } else {
                    alert(response.data.message || 'An error occurred. Please try again.');
                    if (stepContent) {
                        stepContent.style.opacity = '1';
                        stepContent.style.pointerEvents = 'auto';
                    }
                }
            },
            error: function() {
                alert('An error occurred while loading the form. Please refresh the page.');
                if (stepContent) {
                    stepContent.style.opacity = '1';
                    stepContent.style.pointerEvents = 'auto';
                }
            }
        });
    }
    
    function updateProgressIndicators(step) {
        var progressSteps = document.querySelectorAll('.dmr-progress-step');
        progressSteps.forEach(function(progressStep, index) {
            var stepNum = index + 1;
            if (stepNum <= step) {
                progressStep.classList.add('active');
            } else {
                progressStep.classList.remove('active');
            }
        });
    }
    
    function buildReviewContent() {
        // Get answers
        var answers = {};
        var answerInputs = document.querySelectorAll('input[name^="answers["]:checked');
        answerInputs.forEach(function(input) {
            var match = input.name.match(/\[(\d+)\]/);
            if (match) {
                answers[match[1]] = parseInt(input.value);
            }
        });
        
        // Get personal info
        var fullName = document.getElementById('full_name') ? document.getElementById('full_name').value : '';
        var email = document.getElementById('email') ? document.getElementById('email').value : '';
        var phone = document.getElementById('phone') ? document.getElementById('phone').value : '';
        
        // Calculate score
        var reversedItems = [3, 4, 6, 7]; // 0-indexed: 4,5,7,8 become 3,4,6,7
        var score = 0;
        for (var i = 0; i < 10; i++) {
            var value = answers[i] || 0;
            if (reversedItems.indexOf(i) !== -1) {
                score += (4 - value);
            } else {
                score += value;
            }
        }
        
        // Determine category
        var category = 'Moderate Stress';
        if (score <= 13) {
            category = 'Low Stress';
        } else if (score >= 27) {
            category = 'High Perceived Stress';
        }
        
        // Store in hidden fields for submission
        storeReviewData(answers, score, category);
    }
    
    function storeReviewData(answers, score, category) {
        var reviewContent = document.getElementById('dmr-review-content');
        if (!reviewContent) return;
        
        var categoryClass = 'moderate';
        if (score <= 13) categoryClass = 'low';
        else if (score >= 27) categoryClass = 'high';
        
        var html = '<div class="dmr-review-summary">';
        html += '<h4>Your Information</h4>';
        html += '<p><strong>Name:</strong> ' + (document.getElementById('full_name') ? document.getElementById('full_name').value : '') + '</p>';
        html += '<p><strong>Email:</strong> ' + (document.getElementById('email') ? document.getElementById('email').value : '') + '</p>';
        if (document.getElementById('phone') && document.getElementById('phone').value) {
            html += '<p><strong>Phone:</strong> ' + document.getElementById('phone').value + '</p>';
        }
        html += '<h4>Your Score</h4>';
        html += '<div style="text-align: center; padding: 20px; background: #f9f9f9; border-radius: 4px; margin: 15px 0;">';
        html += '<div style="font-size: 48px; font-weight: bold; color: #0073aa;">' + score + '</div>';
        html += '<div style="font-size: 14px; color: #666;">out of 40</div>';
        html += '<div style="margin-top: 10px;"><span style="display: inline-block; padding: 5px 15px; background: ';
        if (categoryClass === 'low') html += '#46b450';
        else if (categoryClass === 'moderate') html += '#ffb900';
        else html += '#dc3232';
        html += '; color: white; border-radius: 3px;">' + category + '</span></div>';
        html += '</div>';
        html += '<p style="color: #666; font-size: 14px;">You answered ' + Object.keys(answers).length + ' of 10 questions.</p>';
        html += '</div>';
        
        reviewContent.innerHTML = html;
    }
    
    // Add visual feedback for selected radio buttons
    function initRadioButtonStyling() {
        var radioButtons = document.querySelectorAll('.dmr-radio-label input[type="radio"]');
        radioButtons.forEach(function(radio) {
            radio.addEventListener('change', function() {
                // Remove checked class from all labels in the same question
                var question = this.closest('.dmr-question');
                if (question) {
                    var allLabels = question.querySelectorAll('.dmr-radio-label');
                    allLabels.forEach(function(label) {
                        label.classList.remove('dmr-radio-checked');
                    });
                    // Add checked class to the selected label
                    this.closest('.dmr-radio-label').classList.add('dmr-radio-checked');
                }
            });
            
            // Set initial state
            if (radio.checked) {
                radio.closest('.dmr-radio-label').classList.add('dmr-radio-checked');
            }
        });
    }
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRadioButtonStyling);
    } else {
        initRadioButtonStyling();
    }
    
})();