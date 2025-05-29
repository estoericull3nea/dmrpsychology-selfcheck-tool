
(function() {
    'use strict';
    
    // Initialize form submission handler
    function initFormSubmission() {
        var form = document.getElementById('dmr-form');
        if (!form) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm();
        });
    }
    
    // Navigation functions
    window.dmrNextStep = function(step) {
        console.log('dmrNextStep called for step', step);
        
        // ALWAYS save form data before validation
        saveFormData();
        
        if (validateCurrentStep()) {
            updateStepInForm(step);
            navigateToStep(step);
        }
    };
    
    window.dmrPrevStep = function(step) {
        console.log('dmrPrevStep called for step', step);
        
        // Save form data before navigating back
        saveFormData();
        
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
        
        // Save current form data before navigating
        saveFormData();
        
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
                            
                            // After content is loaded, restore form data and initialize
                            setTimeout(function() {
                                // Restore form data AFTER HTML is inserted
                                restoreFormData();
                                
                                // Re-initialize radio button styling
                                initRadioButtonStyling();
                                
                                // Store questions if step 1
                                if (response.data.step === 1) {
                                    storeQuestions();
                                }
                                
                                // If step 3, build review content
                                if (response.data.step === 3) {
                                    buildReviewContent();
                                }
                            }, 50);
                        }, 150);
                    }
                    
                    // Update hidden step field
                    var stepField = document.getElementById('dmr_current_step');
                    if (stepField) {
                        stepField.value = response.data.step;
                    }
                    
                    // Update progress indicators
                    updateProgressIndicators(response.data.step);
                    
                    // Scroll to top of form
                    setTimeout(function() {
                        if (form) {
                            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 250);
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
    
    // Store questions and form data
    var storedQuestions = [];
    var storedFormData = {
        answers: {},
        fullName: '',
        email: '',
        phone: '',
        notes: ''
    };
    
    function storeQuestions() {
        storedQuestions = [];
        var questionFieldsets = document.querySelectorAll('.dmr-question');
        questionFieldsets.forEach(function(fieldset) {
            var legend = fieldset.querySelector('legend');
            if (legend) {
                var questionText = legend.textContent.replace(/^\d+\.\s*/, ''); // Remove question number
                storedQuestions.push(questionText);
            }
        });
    }
    
    function saveFormData() {
        console.log('saveFormData() called');
        
        // Save answers - Don't reset, just update
        var answerInputs = document.querySelectorAll('input[name^="answers["]:checked');
        console.log('Found checked inputs:', answerInputs.length);
        
        answerInputs.forEach(function(input) {
            var match = input.name.match(/\[(\d+)\]/);
            if (match) {
                storedFormData.answers[match[1]] = input.value;
                console.log('Saved answer [' + match[1] + '] = ' + input.value);
            }
        });
        
        console.log('Total stored answers:', Object.keys(storedFormData.answers).length);
        
        // Save personal info
        var fullNameField = document.getElementById('full_name');
        var emailField = document.getElementById('email');
        var phoneField = document.getElementById('phone');
        var notesField = document.getElementById('notes');
        
        if (fullNameField && fullNameField.value) {
            storedFormData.fullName = fullNameField.value;
            console.log('Saved name:', fullNameField.value);
        }
        if (emailField && emailField.value) {
            storedFormData.email = emailField.value;
            console.log('Saved email:', emailField.value);
        }
        if (phoneField && phoneField.value) {
            storedFormData.phone = phoneField.value;
            console.log('Saved phone:', phoneField.value);
        }
        if (notesField && notesField.value) {
            storedFormData.notes = notesField.value;
            console.log('Saved notes:', notesField.value);
        }
    }
    
    function restoreFormData() {
        console.log('Restoring form data:', storedFormData);
        
        // Restore answers
        var restoredCount = 0;
        for (var key in storedFormData.answers) {
            var input = document.querySelector('input[name="answers[' + key + ']"][value="' + storedFormData.answers[key] + '"]');
            if (input) {
                input.checked = true;
                restoredCount++;
                // Update visual styling for radio button
                var label = input.closest('.dmr-radio-label');
                if (label) {
                    label.classList.add('dmr-radio-checked');
                }
            }
        }
        console.log('Restored ' + restoredCount + ' answers');
        
        // Restore personal info
        var fullNameField = document.getElementById('full_name');
        var emailField = document.getElementById('email');
        var phoneField = document.getElementById('phone');
        var notesField = document.getElementById('notes');
        
        if (fullNameField && storedFormData.fullName) {
            fullNameField.value = storedFormData.fullName;
            console.log('Restored name:', storedFormData.fullName);
        }
        if (emailField && storedFormData.email) {
            emailField.value = storedFormData.email;
            console.log('Restored email:', storedFormData.email);
        }
        if (phoneField && storedFormData.phone) {
            phoneField.value = storedFormData.phone;
            console.log('Restored phone:', storedFormData.phone);
        }
        if (notesField && storedFormData.notes) {
            notesField.value = storedFormData.notes;
            console.log('Restored notes:', storedFormData.notes);
        }
    }
    
    function buildReviewContent() {
        console.log('=== Building Review Content ===');
        console.log('Stored Questions:', storedQuestions);
        console.log('Stored Form Data:', storedFormData);
        
        // ALWAYS use stored data since we're on step 3 and the form fields aren't available
        var answers = {};
        for (var key in storedFormData.answers) {
            answers[key] = parseInt(storedFormData.answers[key]);
        }
        
        // Use stored questions
        var questions = storedQuestions.slice(); // Create a copy
        
        // Use stored personal info
        var fullName = storedFormData.fullName || '';
        var email = storedFormData.email || '';
        var phone = storedFormData.phone || '';
        var notes = storedFormData.notes || '';
        
        console.log('Answers count:', Object.keys(answers).length);
        console.log('Questions count:', questions.length);
        
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
        var categoryClass = 'moderate';
        if (score <= 13) {
            category = 'Low Stress';
            categoryClass = 'low';
        } else if (score >= 27) {
            category = 'High Perceived Stress';
            categoryClass = 'high';
        }
        
        // Check if we have enough data to build review
        console.log('Validation - Questions:', questions.length, 'Answers:', Object.keys(answers).length);
        
        if (questions.length === 0 || Object.keys(answers).length === 0) {
            var reviewContent = document.getElementById('dmr-review-content');
            if (reviewContent) {
                var errorMsg = '<p class="dmr-loading" style="color: #dc3232;">';
                errorMsg += 'Please complete the previous steps first.<br>';
                errorMsg += 'Questions: ' + questions.length + '/10<br>';
                errorMsg += 'Answers: ' + Object.keys(answers).length + '/10<br>';
                errorMsg += '<button type="button" class="dmr-btn dmr-btn-secondary" onclick="dmrPrevStep(1)" style="margin-top: 15px;">Go Back to Questions</button>';
                errorMsg += '</p>';
                reviewContent.innerHTML = errorMsg;
            }
            console.error('Not enough data to build review');
            return;
        }
        
        console.log('Review validation passed, building content...');
        
        // Build review content
        var reviewContent = document.getElementById('dmr-review-content');
        if (!reviewContent) return;
        
        var scaleLabels = ['Never', 'Almost Never', 'Sometimes', 'Fairly Often', 'Very Often'];
        
        var html = '<div class="dmr-review-summary">';
        
        // User Information Section
        html += '<div class="dmr-review-section">';
        html += '<h4>Your Information</h4>';
        html += '<div class="dmr-review-info">';
        html += '<p><strong>Name:</strong> ' + (fullName || 'Not provided') + '</p>';
        html += '<p><strong>Email:</strong> ' + (email || 'Not provided') + '</p>';
        if (phone) {
            html += '<p><strong>Phone:</strong> ' + phone + '</p>';
        }
        if (notes) {
            html += '<p><strong>Additional Notes:</strong> ' + notes + '</p>';
        }
        html += '</div>';
        html += '</div>';
        
        // Questions and Answers Section
        html += '<div class="dmr-review-section">';
        html += '<h4>Your Answers</h4>';
        html += '<div class="dmr-review-questions">';
        for (var i = 0; i < questions.length; i++) {
            var answerValue = answers[i] !== undefined ? answers[i] : 'Not answered';
            var answerLabel = answerValue !== 'Not answered' ? answerValue + ' - ' + scaleLabels[answerValue] : answerValue;
            
            html += '<div class="dmr-review-question-item">';
            html += '<div class="dmr-review-question-number">' + (i + 1) + '.</div>';
            html += '<div class="dmr-review-question-content">';
            html += '<div class="dmr-review-question-text">' + questions[i] + '</div>';
            html += '<div class="dmr-review-answer">';
            html += '<span class="dmr-review-answer-label">Your Answer:</span> ';
            html += '<span class="dmr-review-answer-value">' + answerLabel + '</span>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        }
        html += '</div>';
        html += '</div>';
        
        // Score Section
        html += '<div class="dmr-review-section">';
        html += '<h4>Your Results</h4>';
        html += '<div class="dmr-review-score-box">';
        html += '<div class="dmr-review-score-number">' + score + '</div>';
        html += '<div class="dmr-review-score-total">/ 40</div>';
        html += '<div class="dmr-review-category dmr-category-' + categoryClass + '">' + category + '</div>';
        html += '</div>';
        html += '</div>';
        
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
    
    // Initialize form submission handler
    function initFormSubmission() {
        var form = document.getElementById('dmr-form');
        if (!form) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm();
        });
    }
    
    // Submit form via AJAX
    function submitForm() {
        if (typeof dmrAjax === 'undefined') {
            // Fallback to normal form submission
            document.getElementById('dmr-form').submit();
            return;
        }
        
        var form = document.getElementById('dmr-form');
        if (!form) return;
        
        // Validate consent checkbox
        var consent = form.querySelector('input[name="consent"]');
        if (!consent || !consent.checked) {
            alert('You must agree to the consent statement before submitting.');
            if (consent) consent.focus();
            return;
        }
        
        // Show loading state
        showLoadingState();
        
        // Collect form data
        var formData = new FormData(form);
        formData.append('action', 'dmr_submit_form');
        formData.append('nonce', dmrAjax.nonce);
        
        // Get answers
        var answers = {};
        var answerInputs = form.querySelectorAll('input[name^="answers["]:checked');
        answerInputs.forEach(function(input) {
            var match = input.name.match(/\[(\d+)\]/);
            if (match) {
                answers[match[1]] = input.value;
            }
        });
        
        // Add answers to form data
        for (var key in answers) {
            formData.append('answers[' + key + ']', answers[key]);
        }
        
        // Make AJAX request
        jQuery.ajax({
            url: dmrAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show success message briefly
                    showSuccessMessage();
                    
                    // Redirect to results page
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1500);
                } else {
                    hideLoadingState();
                    alert(response.data.message || 'An error occurred. Please try again.');
                }
            },
            error: function() {
                hideLoadingState();
                alert('An error occurred while submitting. Please try again.');
            }
        });
    }
    
    function showLoadingState() {
        var form = document.getElementById('dmr-form');
        if (!form) return;
        
        // Disable form
        form.style.pointerEvents = 'none';
        form.style.opacity = '0.6';
        
        // Create loading overlay
        var loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'dmr-submit-loading';
        loadingOverlay.className = 'dmr-submit-loading';
        loadingOverlay.innerHTML = '<div class="dmr-loading-spinner"></div><p class="dmr-loading-text">Submitting...</p>';
        form.appendChild(loadingOverlay);
        
        // Disable submit button
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="dmr-btn-loading">Submitting...</span>';
        }
    }
    
    function hideLoadingState() {
        var form = document.getElementById('dmr-form');
        if (!form) return;
        
        form.style.pointerEvents = 'auto';
        form.style.opacity = '1';
        
        var loadingOverlay = document.getElementById('dmr-submit-loading');
        if (loadingOverlay) {
            loadingOverlay.remove();
        }
        
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Submit';
        }
    }
    
    function showSuccessMessage() {
        var form = document.getElementById('dmr-form');
        if (!form) return;
        
        var successMsg = document.createElement('div');
        successMsg.className = 'dmr-submit-success';
        successMsg.innerHTML = '<p>✓ Submission successful! Redirecting to results...</p>';
        form.appendChild(successMsg);
    }
    
    // Auto-save form data on input changes
    var autoSaveInitialized = false;
    
    function initAutoSave() {
        if (autoSaveInitialized) {
            console.log('Auto-save already initialized');
            return;
        }
        
        console.log('Initializing auto-save...');
        
        // Use event delegation on document since form might be reloaded via AJAX
        document.addEventListener('change', function(e) {
            var form = e.target.closest('#dmr-form');
            if (!form) return;
            
            if (e.target.type === 'radio' && e.target.name && e.target.name.indexOf('answers[') === 0) {
                console.log('Radio button changed:', e.target.name, '=', e.target.value);
                saveFormData();
            } else if (e.target.id === 'full_name' || e.target.id === 'email' || e.target.id === 'phone' || e.target.id === 'notes') {
                console.log('Field changed:', e.target.id);
                saveFormData();
            }
        });
        
        // Also save on input events for text fields
        document.addEventListener('input', function(e) {
            var form = e.target.closest('#dmr-form');
            if (!form) return;
            
            if (e.target.id === 'full_name' || e.target.id === 'email' || e.target.id === 'phone' || e.target.id === 'notes') {
                saveFormData();
            }
        });
        
        autoSaveInitialized = true;
        console.log('Auto-save initialized');
    }
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initRadioButtonStyling();
            initFormSubmission();
            initAutoSave();
            storeQuestions(); // Store questions on initial load
            saveFormData(); // Save initial form data
        });
    } else {
        initRadioButtonStyling();
        initFormSubmission();
        initAutoSave();
        storeQuestions(); // Store questions on initial load
        saveFormData(); // Save initial form data
    }
    
})();