let eduIndex = 0;
let expIndex = 0;
let trainingIndex = 0;
let eligibilityIndex = 0;

window.addEducation = function() {
    document.getElementById('educationWrapper').insertAdjacentHTML('beforeend', `
        <div class="border border-gray-200 rounded-3xl p-6 bg-gray-50 relative">
            <button type="button" onclick="this.parentElement.remove()" class="absolute top-5 right-5 remove-btn">
                Remove
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                <div class="form-field">
                    <input name="education[${eduIndex}][level]" placeholder=" " class="form-input">
                    <label class="floating-label">Level</label>
                </div>
                <div class="form-field">
                    <input name="education[${eduIndex}][school]" placeholder=" " class="form-input">
                    <label class="floating-label">School</label>
                </div>
                <div class="form-field">
                    <input name="education[${eduIndex}][degree]" placeholder=" " class="form-input">
                    <label class="floating-label">Degree / Course</label>
                </div>
                <div class="form-field">
                    <input name="education[${eduIndex}][year_graduated]" placeholder=" " class="form-input">
                    <label class="floating-label">Year Graduated</label>
                </div>
            </div>
        </div>
    `);
    eduIndex++;
}

window.addExperience = function() {
    document.getElementById('experienceWrapper').insertAdjacentHTML('beforeend', `
        <div class="border border-gray-200 rounded-3xl p-6 bg-gray-50 relative">
            <button type="button" onclick="this.parentElement.remove()" class="absolute top-5 right-5 remove-btn">
                Remove
            </button>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="form-field">
                    <input name="experience[${expIndex}][title]" placeholder=" " class="form-input">
                    <label class="floating-label">Position Title</label>
                </div>
                <div class="form-field">
                    <input name="experience[${expIndex}][company]" placeholder=" " class="form-input">
                    <label class="floating-label">Company / Office</label>
                </div>
                <div class="form-field">
                    <input name="experience[${expIndex}][years_months]" placeholder=" " class="form-input">
                    <label class="floating-label">Years / Months</label>
                </div>
            </div>
        </div>
    `);
    expIndex++;
}

window.addTraining = function() {
    document.getElementById('trainingWrapper').insertAdjacentHTML('beforeend', `
        <div class="border border-gray-200 rounded-3xl p-6 bg-gray-50 relative">
            <button type="button" onclick="this.parentElement.remove()" class="absolute top-5 right-5 remove-btn">
                Remove
            </button>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="form-field">
                    <input name="training[${trainingIndex}][title]" placeholder=" " class="form-input">
                    <label class="floating-label">Training Title</label>
                </div>
                <div class="form-field">
                    <input name="training[${trainingIndex}][hours]" placeholder=" " class="form-input">
                    <label class="floating-label">No. of Hours</label>
                </div>
                <div class="form-field">
                    <input name="training[${trainingIndex}][details]" placeholder=" " class="form-input">
                    <label class="floating-label">Details</label>
                </div>
            </div>
        </div>
    `);
    trainingIndex++;
}

window.addEligibility = function() {

    document.getElementById('eligibilityWrapper').insertAdjacentHTML('beforeend', `

        <div class="border border-gray-200 rounded-3xl p-6 bg-gray-50 relative">

            <button
                type="button"
                onclick="this.parentElement.remove()"
                class="absolute top-5 right-5 remove-btn">
                Remove
            </button>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-5">

                <div class="form-field">
                    <input
                        name="eligibility[${eligibilityIndex}][license_name]"
                        placeholder=" "
                        class="form-input">

                    <label class="floating-label">
                        License / Eligibility Name
                    </label>
                </div>

                <div class="form-field">
                    <input
                        name="eligibility[${eligibilityIndex}][rating]"
                        placeholder=" "
                        class="form-input">

                    <label class="floating-label">
                        Rating
                    </label>
                </div>

                <div class="space-y-3">

                    <div class="form-field">
                        <input
                            type="date"
                            name="eligibility[${eligibilityIndex}][valid_until]"
                            class="form-input validity-date">

                        <label class="floating-label">
                            Valid Until
                        </label>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-600">

                        <input
                            type="checkbox"
                            name="eligibility[${eligibilityIndex}][never_expires]"

                            onchange="
                                const wrapper = this.closest('.space-y-3');
                                const input = wrapper.querySelector('.validity-date');

                                if(this.checked){
                                    input.disabled = true;
                                    input.value = '';
                                } else {
                                    input.disabled = false;
                                }
                            ">

                        Never Expires
                    </label>
                </div>

            </div>
        </div>
    `);

    eligibilityIndex++;
}

// Initialize structural defaults on page readiness
document.addEventListener('DOMContentLoaded', () => {

    if (!document.getElementById('educationWrapper').children.length) {
        addEducation();
    }

    if (!document.getElementById('experienceWrapper').children.length) {
        addExperience();
    }

    if (!document.getElementById('trainingWrapper').children.length) {
        addTraining();
    }

    if (!document.getElementById('eligibilityWrapper').children.length) {
        addEligibility();
    }
});