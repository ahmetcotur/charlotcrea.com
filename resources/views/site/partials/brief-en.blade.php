<section class="form-shell">
    <div>
        <p class="bodytext">Tell us a little about your project and the work you have in mind.</p>
        <p class="form-note">01 · Share your brief<br>02 · Discuss scope and timing<br>03 · Shape the production</p>
        <p class="demo-notice">Your brief is saved in the Contact enquiries section of the admin panel.</p>
    </div>
    <div class="formbox">
        @if(session('inquiry-success'))<p class="form-success" role="status">{{ session('inquiry-success') }}</p>@endif
        @if($errors->any())<p class="form-error" role="alert">Please check the highlighted fields and try again.</p>@endif
        <div class="stepbar"><span class="done"></span><span></span><span></span></div>
        <form id="brief" method="POST" action="{{ route('contact-inquiries.store') }}" data-initial-step="{{ $errors->any() ? 2 : 0 }}" @if(session('inquiry-success')) hidden @endif>
            @csrf
            <input type="hidden" name="locale" value="en">
            <label class="contact-honeypot" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
            <div class="form-step">
                <span class="label">Step 1 / Your project</span><h2>Where does the story begin?</h2>
                <label>Company or project name<input name="property" required autocomplete="organization" value="{{ old('property') }}" placeholder="Your company or project name"></label>
                <label>Country<select required name="country"><option value="">{{ __('Select country') }}</option>@foreach($site->catalog(\App\Models\Destination::class)->whereNull('parent_id') as $destination)<option value="{{ $destination->country ?: $destination->translate('en')->title }}" @selected(old('country') === ($destination->country ?: $destination->translate('en')->title))>{{ $destination->translate('en')->title }}</option>@endforeach<option value="Other" @selected(old('country') === 'Other')>Other</option></select></label>
                <label>City or destination<input name="city" required value="{{ old('city') }}" placeholder="e.g. Dubai"></label>
            </div>
            <div class="form-step">
                <span class="label">Step 2 / The vision</span><h2>What would you like to create?</h2>
                <label>Service<select name="service" required><option value="">Select a service</option><option @selected(old('service') === 'Photography')>Photography</option><option @selected(old('service') === 'Film production')>Film production</option><option @selected(old('service') === 'Photography & film')>Photography &amp; film</option></select></label>
                <label>Preferred timing<input name="timing" value="{{ old('timing') }}" placeholder="Dates or estimated month"></label>
                <label>A few words about the project<textarea name="details" required placeholder="Spaces, deliverables, references…">{{ old('details') }}</textarea></label>
            </div>
            <div class="form-step">
                <span class="label">Step 3 / Your details</span><h2>Let’s make the introduction.</h2>
                <label>Your name<input name="name" required autocomplete="name" value="{{ old('name') }}"></label>
                <label>Work email<input name="email" type="email" required autocomplete="email" value="{{ old('email') }}"></label>
                <div id="review" class="review"></div>
                <label class="consent"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))> I agree to the use of this information to respond to my project enquiry.</label>
                <p class="demo-notice">Your enquiry will be saved securely for the team.</p>
            </div>
            <div class="form-actions">
                <button type="button" id="back" class="btn light" hidden>Back</button>
                <button type="submit" id="next" class="btn">Continue ↗</button>
            </div>
        </form>
        <div id="complete" @if(!session('inquiry-success')) hidden @endif><span class="label">Enquiry received</span><h2 style="margin-top:20px">Your brief is with our team.</h2><p class="bodytext">{{ session('inquiry-success') ?: 'We will be in touch soon.' }}</p></div>
    </div>
</section>
