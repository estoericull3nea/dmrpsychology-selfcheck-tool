
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
            
            // Build review content
            buildReviewContent();
            return true;
        }
        
        return true;
    }
    
    function updateStepInForm(step) {
        document.getElementById('dmr_current_step').value = step;
    }
    
    function navigateToStep(step) {
        var url = new URL(window.location.href);
        url.searchParams.set('dmr_step', step);
        window.location.href = url.toString();
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
    
})();