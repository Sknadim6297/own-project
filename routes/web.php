<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Admin Controllers
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\HowworksController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\AboutBannerController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\MCQController;
use App\Http\Controllers\Admin\HelpController;
use App\Http\Controllers\CurrencyController;

// Auth Controllers
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Frontend\AuthController;

// Frontend Controllers
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\LoginController;
use App\Http\Controllers\Frontend\EventController;
use App\Http\Controllers\Frontend\ServiceController;
use App\Http\Controllers\Frontend\SearchController;

// Customer & Professional
use App\Http\Controllers\Customer\UpcomingAppointmentController;
use App\Http\Controllers\Customer\CustomerBookingController;
use App\Http\Controllers\Professional\ProfessionalController;
use App\Http\Controllers\Professional\BillingController;
use App\Http\Controllers\OnboardingController;

// Models
use App\Models\AboutUs;
use App\Models\Whychoose;
use App\Models\Testimonial;
use App\Models\AboutBanner;
use App\Models\AboutExperience;
use App\Models\AboutHowWeWork;
use App\Models\AboutFAQ;
use App\Models\Event;
use App\Models\EventDetail;
use App\Models\EventFAQ;
use App\Models\Contactbanner;
use App\Models\ContactDetail;
use App\Models\BlogBanner;
use App\Models\BlogPost;
use App\Models\Blog;
use App\Models\Professional;
use App\Models\Service;
use App\Models\AllEvent;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\Customer\BookingController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/run-plans-seeder', function () {
    Artisan::call('db:seed', ['--class' => 'PlansSeeder']);
    return "Plans Seeder ran successfully!";
});

Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

// Route to check login status (no authentication required)
Route::post('/check-login', [BookingController::class, 'checkLogin'])->name('check.login');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('gridlisting', [HomeController::class, 'explore'])->name('gridlisting');
Route::get('tools/psychology/mood/history', [\App\Http\Controllers\Frontend\MoodCheckinController::class, 'history'])->name('tools.psychology.mood.history');
Route::get('tools/psychology/mood/finish', [\App\Http\Controllers\Frontend\MoodCheckinController::class, 'finish'])->name('tools.psychology.mood.finish');
Route::get('tools/psychology/mood/complete/{checkin}', [\App\Http\Controllers\Frontend\MoodCheckinController::class, 'saved'])
    ->whereUuid('checkin')
    ->name('tools.psychology.mood.complete');
Route::get('tools/psychology/mood/reflection', [\App\Http\Controllers\Frontend\MoodCheckinController::class, 'reflection'])->name('tools.psychology.mood.reflection');
Route::post('tools/psychology/mood/reflection', [\App\Http\Controllers\Frontend\MoodCheckinController::class, 'storeReflection'])->name('tools.psychology.mood.reflection.store');
Route::get('tools/psychology/mood/emotions', [\App\Http\Controllers\Frontend\MoodCheckinController::class, 'emotions'])->name('tools.psychology.mood.emotions');
Route::post('tools/psychology/mood/emotions', [\App\Http\Controllers\Frontend\MoodCheckinController::class, 'storeEmotions'])->name('tools.psychology.mood.emotions.store');
Route::get('tools/psychology/mood', [\App\Http\Controllers\Frontend\MoodCheckinController::class, 'create'])->name('tools.psychology.mood');
Route::post('tools/psychology/mood', [\App\Http\Controllers\Frontend\MoodCheckinController::class, 'store'])->name('tools.psychology.mood.store');
Route::get('tools/psychology/gratitude/finish', [\App\Http\Controllers\Frontend\GratitudeJournalController::class, 'finish'])->name('tools.psychology.gratitude.finish');
Route::get('tools/psychology/gratitude/complete/{entry}', [\App\Http\Controllers\Frontend\GratitudeJournalController::class, 'saved'])
    ->whereUuid('entry')
    ->name('tools.psychology.gratitude.complete');
Route::get('tools/psychology/gratitude/history', [\App\Http\Controllers\Frontend\GratitudeJournalController::class, 'history'])->name('tools.psychology.gratitude.history');
Route::get('tools/psychology/gratitude/entries/{entry}/edit', [\App\Http\Controllers\Frontend\GratitudeJournalController::class, 'edit'])
    ->whereNumber('entry')
    ->name('tools.psychology.gratitude.edit');
Route::put('tools/psychology/gratitude/entries/{entry}', [\App\Http\Controllers\Frontend\GratitudeJournalController::class, 'update'])
    ->whereNumber('entry')
    ->name('tools.psychology.gratitude.update');
Route::delete('tools/psychology/gratitude/entries/{entry}', [\App\Http\Controllers\Frontend\GratitudeJournalController::class, 'destroy'])
    ->whereNumber('entry')
    ->name('tools.psychology.gratitude.destroy');
Route::get('tools/psychology/gratitude/entries/{entry}', [\App\Http\Controllers\Frontend\GratitudeJournalController::class, 'show'])
    ->whereNumber('entry')
    ->name('tools.psychology.gratitude.show');
Route::get('tools/psychology/gratitude', [\App\Http\Controllers\Frontend\GratitudeJournalController::class, 'create'])->name('tools.psychology.gratitude');
Route::post('tools/psychology/gratitude', [\App\Http\Controllers\Frontend\GratitudeJournalController::class, 'store'])->name('tools.psychology.gratitude.store');
Route::get('tools/psychology/journal/finish', [\App\Http\Controllers\Frontend\DigitalJournalController::class, 'finish'])->name('tools.psychology.journal.finish');
Route::get('tools/psychology/journal/history', [\App\Http\Controllers\Frontend\DigitalJournalController::class, 'history'])->name('tools.psychology.journal.history');
Route::get('tools/psychology/journal/entries/{entry}/edit', [\App\Http\Controllers\Frontend\DigitalJournalController::class, 'edit'])
    ->whereNumber('entry')
    ->name('tools.psychology.journal.edit');
Route::put('tools/psychology/journal/entries/{entry}', [\App\Http\Controllers\Frontend\DigitalJournalController::class, 'update'])
    ->whereNumber('entry')
    ->name('tools.psychology.journal.update');
Route::delete('tools/psychology/journal/entries/{entry}', [\App\Http\Controllers\Frontend\DigitalJournalController::class, 'destroy'])
    ->whereNumber('entry')
    ->name('tools.psychology.journal.destroy');
Route::get('tools/psychology/journal/entries/{entry}', [\App\Http\Controllers\Frontend\DigitalJournalController::class, 'show'])
    ->whereNumber('entry')
    ->name('tools.psychology.journal.show');
Route::get('tools/psychology/journal', [\App\Http\Controllers\Frontend\DigitalJournalController::class, 'create'])->name('tools.psychology.journal');
Route::post('tools/psychology/journal', [\App\Http\Controllers\Frontend\DigitalJournalController::class, 'store'])->name('tools.psychology.journal.store');
Route::get('tools/psychology', [HomeController::class, 'psychology'])->name('tools.psychology');

Route::get('tools/nutrition/search', [\App\Http\Controllers\Frontend\NutritionTrackerController::class, 'search'])->name('tools.nutrition.search');
Route::get('tools/nutrition/foods/{food}/portion', [\App\Http\Controllers\Frontend\NutritionTrackerController::class, 'portion'])->name('tools.nutrition.portion');
Route::post('tools/nutrition/entries', [\App\Http\Controllers\Frontend\NutritionTrackerController::class, 'storeEntry'])->middleware('throttle:60,1')->name('tools.nutrition.entries.store');
Route::put('tools/nutrition/entries/{entry}', [\App\Http\Controllers\Frontend\NutritionTrackerController::class, 'updateEntry'])->name('tools.nutrition.entries.update');
Route::delete('tools/nutrition/entries/{entry}', [\App\Http\Controllers\Frontend\NutritionTrackerController::class, 'destroyEntry'])->name('tools.nutrition.entries.destroy');
Route::get('tools/nutrition/meal', [\App\Http\Controllers\Frontend\NutritionTrackerController::class, 'meal'])->name('tools.nutrition.meal');
Route::get('tools/nutrition/insights', [\App\Http\Controllers\Frontend\NutritionTrackerController::class, 'insights'])->name('tools.nutrition.insights');
Route::get('tools/nutrition/goals', [\App\Http\Controllers\Frontend\NutritionTrackerController::class, 'goalsForm'])->name('tools.nutrition.goals');
Route::post('tools/nutrition/goals', [\App\Http\Controllers\Frontend\NutritionTrackerController::class, 'updateGoals'])->middleware('throttle:30,1')->name('tools.nutrition.goals.update');
Route::get('tools/nutrition', [\App\Http\Controllers\Frontend\NutritionTrackerController::class, 'dashboard'])->name('tools.nutrition');

Route::get('tools/weight-journey/goal', [\App\Http\Controllers\Frontend\WeightJourneyController::class, 'goalForm'])->name('tools.weight-journey.goal');

Route::post('tools/weight-journey/goal', [\App\Http\Controllers\Frontend\WeightJourneyController::class, 'saveGoal'])->middleware('throttle:30,1')->name('tools.weight-journey.goal.store');
Route::post('tools/weight-journey/goal/archive', [\App\Http\Controllers\Frontend\WeightJourneyController::class, 'archiveGoal'])->name('tools.weight-journey.goal.archive');
Route::get('tools/weight-journey/log', [\App\Http\Controllers\Frontend\WeightJourneyController::class, 'logForm'])->name('tools.weight-journey.log');
Route::post('tools/weight-journey/measurements', [\App\Http\Controllers\Frontend\WeightJourneyController::class, 'storeMeasurement'])->middleware('throttle:60,1')->name('tools.weight-journey.measurements.store');
Route::put('tools/weight-journey/measurements/{measurement}', [\App\Http\Controllers\Frontend\WeightJourneyController::class, 'updateMeasurement'])->name('tools.weight-journey.measurements.update');
Route::delete('tools/weight-journey/measurements/{measurement}', [\App\Http\Controllers\Frontend\WeightJourneyController::class, 'destroyMeasurement'])->name('tools.weight-journey.measurements.destroy');
Route::get('tools/weight-journey/progress', [\App\Http\Controllers\Frontend\WeightJourneyController::class, 'progress'])->name('tools.weight-journey.progress');
Route::get('tools/weight-journey', [\App\Http\Controllers\Frontend\WeightJourneyController::class, 'journey'])->name('tools.weight-journey');

Route::get('tools/water-intake/today', [\App\Http\Controllers\Frontend\WaterIntakeController::class, 'today'])->name('tools.water-intake.today');
Route::get('tools/water-intake/week', [\App\Http\Controllers\Frontend\WaterIntakeController::class, 'week'])->name('tools.water-intake.week');
Route::get('tools/water-intake/goal', [\App\Http\Controllers\Frontend\WaterIntakeController::class, 'editGoal'])->name('tools.water-intake.goal');
Route::post('tools/water-intake/goal', [\App\Http\Controllers\Frontend\WaterIntakeController::class, 'updateGoal'])->name('tools.water-intake.goal.update');
Route::post('tools/water-intake/entries', [\App\Http\Controllers\Frontend\WaterIntakeController::class, 'store'])->middleware('throttle:60,1')->name('tools.water-intake.store');
Route::put('tools/water-intake/entries/{entry}', [\App\Http\Controllers\Frontend\WaterIntakeController::class, 'update'])->name('tools.water-intake.entries.update');
Route::delete('tools/water-intake/entries/{entry}', [\App\Http\Controllers\Frontend\WaterIntakeController::class, 'destroy'])->name('tools.water-intake.entries.destroy');
Route::get('tools/water-intake', [\App\Http\Controllers\Frontend\WaterIntakeController::class, 'dashboard'])->name('tools.water-intake');

Route::get('tools/bmi-bmr/history', [\App\Http\Controllers\Frontend\BmiBmrController::class, 'history'])->name('tools.bmi-bmr.history');
Route::get('tools/bmi-bmr/history/{calculation}', [\App\Http\Controllers\Frontend\BmiBmrController::class, 'show'])->name('tools.bmi-bmr.history.show');
Route::delete('tools/bmi-bmr/history/{calculation}', [\App\Http\Controllers\Frontend\BmiBmrController::class, 'destroy'])->name('tools.bmi-bmr.history.destroy');
Route::get('tools/bmi-bmr/finish', [\App\Http\Controllers\Frontend\BmiBmrController::class, 'finish'])->name('tools.bmi-bmr.finish');
Route::post('tools/bmi-bmr/save', [\App\Http\Controllers\Frontend\BmiBmrController::class, 'save'])->name('tools.bmi-bmr.save');
Route::post('tools/bmi-bmr/recalculate', [\App\Http\Controllers\Frontend\BmiBmrController::class, 'recalculate'])->name('tools.bmi-bmr.recalculate');
Route::get('tools/bmi-bmr/guidance', [\App\Http\Controllers\Frontend\BmiBmrController::class, 'guidance'])->name('tools.bmi-bmr.guidance');
Route::get('tools/bmi-bmr/understand', [\App\Http\Controllers\Frontend\BmiBmrController::class, 'understand'])->name('tools.bmi-bmr.understand');
Route::get('tools/bmi-bmr/results', [\App\Http\Controllers\Frontend\BmiBmrController::class, 'results'])->name('tools.bmi-bmr.results');
Route::post('tools/bmi-bmr/calculate', [\App\Http\Controllers\Frontend\BmiBmrController::class, 'calculate'])->name('tools.bmi-bmr.calculate');
Route::get('tools/bmi-bmr', [\App\Http\Controllers\Frontend\BmiBmrController::class, 'create'])->name('tools.bmi-bmr');

Route::get('tools/skills-strengths/history', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'history'])->name('tools.skills-strengths.history');
Route::get('tools/skills-strengths/finish', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'finish'])->name('tools.skills-strengths.finish');
Route::get('tools/skills-strengths/result/{attempt}', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'result'])->name('tools.skills-strengths.result');
Route::delete('tools/skills-strengths/result/{attempt}', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'destroy'])->name('tools.skills-strengths.destroy');
Route::post('tools/skills-strengths/retake', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'retake'])->name('tools.skills-strengths.retake');
Route::post('tools/skills-strengths/save', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'save'])->name('tools.skills-strengths.save');
Route::get('tools/skills-strengths/profile', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'profile'])->name('tools.skills-strengths.profile');
Route::get('tools/skills-strengths/intro', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'intro'])->name('tools.skills-strengths.intro');
Route::post('tools/skills-strengths/intro', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'storeIntro'])->name('tools.skills-strengths.intro.store');
Route::get('tools/skills-strengths/category/{category}', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'category'])->name('tools.skills-strengths.category');
Route::post('tools/skills-strengths/category/{category}', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'storeCategory'])->name('tools.skills-strengths.category.store');
Route::post('tools/skills-strengths/start', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'start'])->name('tools.skills-strengths.start');
Route::get('tools/skills-strengths', [\App\Http\Controllers\Frontend\SkillsStrengthsController::class, 'welcome'])->name('tools.skills-strengths');

Route::get('tools/career-interest/history', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'history'])->name('tools.career-interest.history');
Route::get('tools/career-interest/finish', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'finish'])->name('tools.career-interest.finish');
Route::get('tools/career-interest/result/{attempt}', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'result'])->name('tools.career-interest.result');
Route::delete('tools/career-interest/result/{attempt}', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'destroy'])->name('tools.career-interest.destroy');
Route::post('tools/career-interest/retake', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'retake'])->name('tools.career-interest.retake');
Route::post('tools/career-interest/save', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'save'])->name('tools.career-interest.save');
Route::get('tools/career-interest/suggestions', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'suggestions'])->name('tools.career-interest.suggestions');
Route::get('tools/career-interest/profile', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'profile'])->name('tools.career-interest.profile');
Route::get('tools/career-interest/about', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'about'])->name('tools.career-interest.about');
Route::post('tools/career-interest/about', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'storeAbout'])->name('tools.career-interest.about.store');
Route::get('tools/career-interest/category/{category}', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'category'])->name('tools.career-interest.category');
Route::post('tools/career-interest/category/{category}', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'storeCategory'])->name('tools.career-interest.category.store');
Route::post('tools/career-interest/start', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'start'])->name('tools.career-interest.start');
Route::get('tools/career-interest', [\App\Http\Controllers\Frontend\CareerInterestController::class, 'welcome'])->name('tools.career-interest');

Route::get('tools', [HomeController::class, 'tools'])->name('tools.index');
Route::post('/currency/switch', [CurrencyController::class, 'switch'])->name('currency.switch');

// Professional details page - public viewing but requires auth for booking
Route::get("professionals/details/{id}/{professional_name?}/{sub_service_slug?}", [HomeController::class, 'professionalsDetails'])->name('professionals.details');

// AJAX routes for dynamic filtering
Route::get('/get-sub-services', [HomeController::class, 'getSubServices'])->name('get.sub.services');
Route::get('/get-professional-rates-availability', [HomeController::class, 'getProfessionalRatesAvailability'])->name('get.professional.rates.availability');
Route::get("professionals/details/{id}/{professional_name?}", [HomeController::class, 'professionalsDetails'])->name('professionals.details.simple');
// Removed conflicting duplicate route name to ensure JSON endpoint resolves correctly
// Route::get('professionals/get-rates-by-sub-service', [HomeController::class, 'getProfessionalRatesBySubService'])->name('get.professional.rates.availability');

Route::get('about', function () {
    $about = AboutUs::latest()->first();
    $aboutImage = ($about && $about->image && is_file(public_path('storage/'.$about->image)))
        ? asset('storage/'.$about->image)
        : null;
    $storyImage = is_file(public_path('frontend/assets/img/professionals_photos/about-work-section.jpeg'))
        ? asset('frontend/assets/img/professionals_photos/about-work-section.jpeg')
        : $aboutImage;
    $services = Service::query()->where('status', 'active')->orderBy('name')->get();
    $testimonials = Testimonial::query()
        ->whereNotNull('description')
        ->where('description', '!=', '')
        ->latest()
        ->get();
    $listedProfessionals = Professional::query()
        ->where('status', 'accepted')
        ->where('active', true)
        ->count();

    return view('frontend.sections.about', compact(
        'aboutImage',
        'storyImage',
        'services',
        'testimonials',
        'listedProfessionals'
    ));
})->name('about');
Route::get('/eventlist', function (Request $request) {
    if (! config('yuktione.programmes_enabled')) {
        return redirect()->route('gridlisting');
    }

    $filter = $request->query('filter');
    $category = $request->query('category');
    $price_range = $request->query('price_range');
    $city = $request->query('city');
    $event_mode = $request->query('event_mode');

    // Get admin events - ONLY approved admin created event details (no homepage toggle requirement for event list)
    $adminEvents = EventDetail::with('event')
        ->where('creator_type', 'admin')
        ->whereHas('event', function ($query) use ($filter, $category, $price_range) {
        // Only show approved events (homepage toggle not required for event list page)
        $query->where('status', 'approved');
        if ($filter == 'today') {
            $query->whereDate('date', Carbon::today()->toDateString());
        } elseif ($filter == 'tomorrow') {
            $query->whereDate('date', Carbon::tomorrow()->toDateString());
        } elseif ($filter == 'weekend') {
            $startOfWeekend = Carbon::now()->next(Carbon::SATURDAY)->startOfDay();
            $endOfWeekend = Carbon::now()->next(Carbon::SUNDAY)->endOfDay();
            $query->whereBetween('date', [$startOfWeekend, $endOfWeekend]);
        }

        if ($category) {
            $query->where('mini_heading', $category);
        }

        if ($price_range) {
            switch ($price_range) {
                case '100-200':
                    $query->whereBetween('starting_fees', [100, 200]);
                    break;
                case '200-300':
                    $query->whereBetween('starting_fees', [200, 300]);
                    break;
                case '300-400':
                    $query->whereBetween('starting_fees', [300, 400]);
                    break;
                case '400-500':
                    $query->whereBetween('starting_fees', [400, 500]);
                    break;
                case '500-1000':
                    $query->whereBetween('starting_fees', [500, 1000]);
                    break;
            }
        }
    });

    // Add city filter for admin events
    if ($city) {
        $adminEvents = $adminEvents->where('city', $city);
    }

    // Add event mode filter for admin events
    if ($event_mode) {
        $adminEvents = $adminEvents->where('event_mode', $event_mode);
    }

    $adminEvents = $adminEvents->latest()->get();

    // Get approved professional events from AllEvent model (no homepage toggle requirement for event list)
    $professionalEvents = AllEvent::with('professional')
        ->where('created_by_type', 'professional')
        ->where('status', 'approved');

    // Apply filters to professional events
    if ($filter == 'today') {
        $professionalEvents = $professionalEvents->whereDate('date', Carbon::today()->toDateString());
    } elseif ($filter == 'tomorrow') {
        $professionalEvents = $professionalEvents->whereDate('date', Carbon::tomorrow()->toDateString());
    } elseif ($filter == 'weekend') {
        $startOfWeekend = Carbon::now()->next(Carbon::SATURDAY)->startOfDay();
        $endOfWeekend = Carbon::now()->next(Carbon::SUNDAY)->endOfDay();
        $professionalEvents = $professionalEvents->whereBetween('date', [$startOfWeekend, $endOfWeekend]);
    }

    if ($category) {
        $professionalEvents = $professionalEvents->where('mini_heading', $category);
    }

    if ($price_range) {
        switch ($price_range) {
            case '100-200':
                $professionalEvents = $professionalEvents->whereBetween('starting_fees', [100, 200]);
                break;
            case '200-300':
                $professionalEvents = $professionalEvents->whereBetween('starting_fees', [200, 300]);
                break;
            case '300-400':
                $professionalEvents = $professionalEvents->whereBetween('starting_fees', [300, 400]);
                break;
            case '400-500':
                $professionalEvents = $professionalEvents->whereBetween('starting_fees', [400, 500]);
                break;
            case '500-1000':
                $professionalEvents = $professionalEvents->whereBetween('starting_fees', [500, 1000]);
                break;
        }
    }

    $professionalEvents = $professionalEvents->latest()->get();

    Route::get('/get-sub-services', [HomeController::class, 'getSubServices'])->name('get.sub.services');

    // Transform professional events to match admin event structure
    $professionalEvents = $professionalEvents->map(function ($event) {
        // Create a pseudo EventDetail structure for consistency with admin events
        $pseudoEventDetail = new stdClass();
        $pseudoEventDetail->id = $event->id;
        $pseudoEventDetail->event = $event;
        return $pseudoEventDetail;
    });

    // Combine both types of events
    $events = collect($adminEvents)->merge($professionalEvents)->sortByDesc('created_at');

    $services = Service::latest()->get();

    // Get unique categories for the filter (from both admin and professional events that are approved)
    $adminCategories = AllEvent::where('created_by_type', 'admin')
        ->where('status', 'approved')
        ->distinct()
        ->pluck('mini_heading');
    $professionalCategories = AllEvent::where('created_by_type', 'professional')
        ->where('status', 'approved')
        ->distinct()
        ->pluck('mini_heading');
    $categories = $adminCategories->merge($professionalCategories)->unique();

    // Get unique cities for the filter
    $cities = EventDetail::distinct()->pluck('city')->filter();

    // Get unique event modes for the filter
    $event_modes = ['online', 'offline'];

    return view('frontend.sections.eventlist', compact('events', 'services', 'filter', 'categories', 'category', 'price_range', 'cities', 'city', 'event_modes', 'event_mode'));
})->name('event.list');
Route::get('/programmes', function (Request $request) {
    if (! config('yuktione.programmes_enabled')) {
        return redirect()->route('gridlisting');
    }

    return redirect()->route('event.list', $request->query(), 302);
})->name('programmes.index');
Route::get('/allevent/{id}', function ($id) {
    if (! config('yuktione.programmes_enabled')) {
        return redirect()->route('gridlisting');
    }

    // Try to find in AllEvent (this table has status column)
    $allEvent = AllEvent::where('id', $id)
        ->where('status', 'approved')
        ->first();

    if ($allEvent && $allEvent->created_by_type === 'professional') {
        // Professional event found
        $services = Service::all();
        $eventfaqs = EventFAQ::latest()->get();
        
        // Get event details if available (EventDetail does NOT have status column)
        $eventDetail = EventDetail::where('event_id', $id)
                                  ->where('creator_type', 'professional')
                                  ->first();

        return view('frontend.sections.allevent', compact('allEvent', 'services', 'eventfaqs', 'eventDetail'));
    } elseif ($allEvent && $allEvent->created_by_type === 'admin') {
        // Admin event found in all_events table
        $event = $allEvent;
        $services = Service::all();
        $eventfaqs = EventFAQ::latest()->get();
        
        // Get event details if available
        $eventDetail = EventDetail::where('event_id', $allEvent->id)
            ->where('creator_type', 'admin')
            ->first();
            
        return view('frontend.sections.allevent', compact('event', 'services', 'eventfaqs', 'eventDetail'));
    }
    
    // Not found in all_events, maybe the ID is an EventDetail ID
    $eventDetail = EventDetail::where('id', $id)
        ->where('creator_type', 'admin')
        ->first();

    if (!$eventDetail || !$eventDetail->event_id) {
        abort(404, 'Event not found');
    }

    // Get the associated event from all_events and check if approved
    $allEvent = AllEvent::where('id', $eventDetail->event_id)
        ->where('status', 'approved')
        ->first();

    if (!$allEvent) {
        abort(404, 'Event not found or not approved');
    }

    $event = $allEvent;
    $services = Service::all();
    $eventfaqs = EventFAQ::latest()->get();

    return view('frontend.sections.allevent', compact('event', 'services', 'eventfaqs', 'eventDetail'));
})->name('event.details');
Route::get('/programmes/{id}', function ($id) {
    if (! config('yuktione.programmes_enabled')) {
        return redirect()->route('gridlisting');
    }

    return redirect()->to(url('/allevent/' . $id), 302);
})->whereNumber('id')->name('programmes.show');
Route::get('allevent', function ($id) {
    if (! config('yuktione.programmes_enabled')) {
        return redirect()->route('gridlisting');
    }

    $eventdetails = Eventdetail::with('event')->latest()->get();
    // dd($eventdetails);
    $eventfaqs = EventFAQ::latest()->get();
    $event = Event::findOrFail($id);

    return view('frontend.sections.allevent', compact('eventdetails', 'eventfaqs', 'event'));
});

Route::get('/allevents', function () {
    if (! config('yuktione.programmes_enabled')) {
        return redirect()->route('gridlisting');
    }

    return app(EventController::class)->index();
})->name('allevents');
Route::get('/service/{service:slug}', [ServiceController::class, 'show'])->name('service.show');
Route::get('/service/{service:slug}/questions', [HomeController::class, 'getServiceQuestions'])->name('service.questions');


Route::get('blog', function (Request $request) {
    $blogbanners = BlogBanner::latest()->get();
    $services = Service::latest()->get();
    $latestBlogs = BlogPost::latest()->take(3)->get();
    $categoryCounts = BlogPost::select('category', DB::raw('count(*) as post_count'))
        ->groupBy('category')
        ->get();
    $search = $request->input('search');
    $category = $request->input('category');

    $blogPosts = BlogPost::with('blog')
        ->when($search, function ($query, $search) {
            return $query->where(function ($q) use ($search) {
                $q->whereHas('blog', function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%');
                })
                    ->orWhere('category', 'like', '%' . $search . '%');
            });
        })
        ->when($category, function ($query, $category) {
            return $query->where('category', $category);
        })
        ->latest()
        ->paginate(9);

    return view('frontend.sections.blog', compact('blogbanners', 'blogPosts', 'services', 'latestBlogs', 'categoryCounts', 'search', 'category'));
})->name('blog.index');
Route::get('/blog-post/{identifier}', function ($identifier) {
    $blogPost = null;

    if (is_numeric($identifier)) {
        // Handle numeric ID
        $blogPost = App\Models\BlogPost::with('blog')->find($identifier);
        if ($blogPost) {
            // Redirect to slug URL for SEO consistency
            $slug = \Illuminate\Support\Str::slug($blogPost->blog->title ?? '');
            if ($slug && $slug !== (string) $identifier) {
                return redirect()->route('blog.show', $slug);
            }
        }
    } else {
        // Handle slug - find by matching slugified title
        $allBlogPosts = App\Models\BlogPost::with('blog')->get();
        foreach ($allBlogPosts as $bp) {
            if (\Illuminate\Support\Str::slug($bp->blog->title ?? '') === $identifier) {
                $blogPost = $bp;
                break;
            }
        }
    }

    // Handle case where blog doesn't exist
    if (!$blogPost) {
        abort(404, 'Blog not found');
    }

    $relatedBlog = DB::table('blogs')->where('id', $blogPost->blog_id)->first();
    $latestBlogs = App\Models\BlogPost::latest()->take(3)->get();
    $categoryCounts = App\Models\BlogPost::select('category', DB::raw('count(*) as post_count'))
        ->groupBy('category')
        ->get();
    $comments = \App\Models\Comment::where('blog_post_id', $blogPost->id)
        ->where('is_approved', true)
        ->latest()
        ->take(4)
        ->get();

    // Fetch latest services
    $services = App\Models\Service::latest()->get();

    return view('frontend.sections.blog-post', compact('blogPost', 'services', 'relatedBlog', 'latestBlogs', 'categoryCounts', 'comments'));
})->name('blog.show');
Route::get('eventdetails', function () {
    return redirect()->route('event.list', [], 302);
});
Route::get('interiordesign', function () {
    return view('frontend.sections.interiordesign');
});
Route::get('pshychology', function () {
    return view('frontend.sections.pshychology');
});
Route::get('fitness', function () {
    return view('frontend.sections.fitness-yoga');
});
Route::get('dieticians', function () {
    return view('frontend.sections.dieticians');
});
Route::get('contact', function () {
    $contactbanners = Contactbanner::latest()->get();
    $contactdetails = ContactDetail::latest()->get();
    $services = Service::latest()->get();
    return view('frontend.sections.contact', compact('contactbanners', 'contactdetails', 'services'));
})->name('contact');

Route::get('influencer', function () {
    return view('frontend.sections.influencer');
});
Route::get('stylist', function () {
    // Legacy landing template is missing; send visitors to Explore.
    return redirect()->route('gridlisting');
});
Route::get('job', function () {
    return view('frontend.sections.job');
});
Route::get('career', function () {
    $careers = \App\Models\Career::where('status', 'active')->latest()->get();
    return view('frontend.sections.career', compact('careers'));
})->name('career');
Route::get('job-details/{id}', function ($id) {
    $career = \App\Models\Career::findOrFail($id);
    return view('frontend.sections.job-details', compact('career'));
})->name('job.details');
Route::post('job-application/store', [\App\Http\Controllers\Frontend\JobApplicationController::class, 'store'])->name('job.application.store');
Route::get('astro', function () {
    return view('frontend.sections.astro');
});
Route::get('blog-post', function () {
    return redirect()->route('blog.index');
});

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('register', [LoginController::class, 'showRegisterForm'])->name('register');

Route::post('login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
Route::post('register', [LoginController::class, 'register'])->name('register.submit');
Route::post('/register/send-otp', [App\Http\Controllers\Frontend\LoginController::class, 'sendOtp'])->name('register.send-otp');
Route::post('/register/verify-otp', [App\Http\Controllers\Frontend\LoginController::class, 'verifyOtp'])->name('register.verify-otp');
Route::post('/register/save-lead', [App\Http\Controllers\Frontend\LoginController::class, 'saveCustomerLead'])->name('register.save-lead');
Route::post('/register/verify-otp', [App\Http\Controllers\Frontend\LoginController::class, 'verifyOtp'])->name('register.verify-otp');
Route::post('/register/create-incomplete', [App\Http\Controllers\Frontend\LoginController::class, 'createIncompleteUser'])->name('register.create-incomplete');
Route::post('/register/complete', [App\Http\Controllers\Frontend\LoginController::class, 'completeRegistration'])->name('register.complete');


Route::get('admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('admin/store', [AdminLoginController::class, 'store'])->name('admin.store');
Route::get('admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');


// Route::prefix('admin')->group(function () {
//     Route::get('/about', [AdminAboutPageController::class, 'edit'])->name('admin.about.edit');
//     Route::put('/about', [AdminAboutPageController::class, 'update'])->name('admin.about.update');
// });

Route::post('/submit-questionnaire', [HomeController::class, 'submitQuestionnaire'])->name('submitQuestionnaire');

// Service session save route (no authentication required for sharing)
Route::post('/set-service-session', [HomeController::class, 'setServiceSession'])->name('set.service.session');

// Professionals listing page (no authentication required for viewing)
Route::get("professionals", [HomeController::class, 'professionals'])->name('professionals');

// Onboarding routes (require authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding/status', [OnboardingController::class, 'getOnboardingStatus'])->name('onboarding.status');
    Route::post('/onboarding/complete-customer', [OnboardingController::class, 'completeCustomerOnboarding'])->name('onboarding.complete.customer');
    Route::post('/onboarding/complete-professional', [OnboardingController::class, 'completeProfessionalOnboarding'])->name('onboarding.complete.professional');
    Route::post('/onboarding/reset-customer', [OnboardingController::class, 'resetCustomerOnboarding'])->name('onboarding.reset.customer');
    Route::post('/onboarding/reset-professional', [OnboardingController::class, 'resetProfessionalOnboarding'])->name('onboarding.reset.professional');
});

Route::middleware(['auth:user'])->group(function () {
    // Upcoming appointments routes
    Route::get('/upcoming-appointments', [UpcomingAppointmentController::class, 'index'])->name('user.upcoming-appointment.index');
    Route::post('/customer/upload-document', [UpcomingAppointmentController::class, 'uploadDocument'])->name('user.upload-document');
    Route::get('/customer/document-info/{id}', [UpcomingAppointmentController::class, 'getDocumentInfo'])->name('user.document-info');

    // Booking and Payment routes
    Route::post('/booking/initiate-payment', [CustomerBookingController::class, 'initiatePayment'])->name('user.booking.initiate-payment');
    Route::post('/booking/verify-payment', [CustomerBookingController::class, 'verifyPayment'])->name('user.booking.verify-payment');
    Route::get('/booking/success', [CustomerBookingController::class, 'success'])->name('user.booking.success');
    Route::get('/event/booking/success', [CustomerBookingController::class, 'eventSuccess'])->name('user.event.booking.success');

    // Customer Billing routes
    Route::get('/customer/billing', [CustomerBookingController::class, 'billing'])->name('user.billing.index');
    Route::get('/customer/billing/download/{id}', [CustomerBookingController::class, 'downloadInvoice'])->name('user.billing.download');

    // New routes for paymentFailed and retryBooking
    Route::post('customer/booking/payment-failed', [CustomerBookingController::class, 'paymentFailed'])->name('user.customer.booking.payment.failed');
    Route::get('customer/booking/retry/{booking_id}', [CustomerBookingController::class, 'retryBooking'])->name('user.customer.booking.retry');

    Route::post('/get-mcq-questions', [CustomerBookingController::class, 'getMCQQuestions'])->name('get.mcq.questions');
    Route::post('/submit-mcq-answers', [CustomerBookingController::class, 'submitMCQAnswers'])->name('user.mcq.submit');
});

Route::get('/admin/banners', [BannerController::class, 'index'])->name('admin.banner.index');
Route::post('/admin/banners', [BannerController::class, 'store'])->name('admin.banner.store');


Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('homeblog', \App\Http\Controllers\Admin\HomeBlogController::class);
    Route::resource('howworks', \App\Http\Controllers\Admin\HowworksController::class);
    Route::resource('about-banner', AboutBannerController::class);
    Route::get('/mcq-answers', [App\Http\Controllers\Admin\McqAnswerController::class, 'index'])->name('mcq-answers.index');
    Route::get('/contact-forms', [App\Http\Controllers\Admin\ContactFormController::class, 'index'])->name('contact-forms.index');
    Route::get('/contact-forms/export', [App\Http\Controllers\Admin\ContactFormController::class, 'export'])->name('contact-forms.export');
    Route::delete('/contact-forms/{contactForm}', [App\Http\Controllers\Admin\ContactFormController::class, 'destroy'])->name('contact-forms.destroy');
});


Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('aboutus', \App\Http\Controllers\Admin\AboutUsController::class);
});
Route::get('professional/login', [ProfessionalController::class, 'showLoginForm'])->name('professional.login');
Route::post('professional/store', [ProfessionalController::class, 'store'])->middleware('throttle:5,1')->name('professional.store');
Route::get('professional/logout', [ProfessionalController::class, 'logout'])->name('professional.logout');
Route::get('professional/register', [ProfessionalController::class, 'registerForm'])->name('professional.register');
Route::post('professional/register', [ProfessionalController::class, 'register'])->name('professional.register.submit');
Route::post('professional/send-otp', [ProfessionalController::class, 'sendOTP'])->name('professional.send.otp');
Route::post('professional/verify-otp', [ProfessionalController::class, 'verifyOTP'])->name('professional.verify.otp');


// Route::get('/get-mcqs/{service_id}', [ServiceController::class, 'getMcqs']);
Route::post('/submit-mcq', [MCQController::class, 'store'])->name('submit.mcq');
Route::get('/get-mcq-questions/{serviceId}', [HomeController::class, 'getServiceQuestions']);
// Forgot Password
Route::get('/forgot-password', [AuthController::class, 'showForgotForm'])->name('forgot.form');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
    ->middleware(['throttle:5,1,recovery', 'throttle:20,60,recovery-hour'])
    ->name('forgot.send');

// Reset Password
Route::get('/reset-password', [AuthController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

// Add these routes for user password reset
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showForgotForm'])
        ->name('forgot.form');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLink'])
        ->middleware(['throttle:5,1,recovery', 'throttle:20,60,recovery-hour'])
        ->name('forgot.send');
    Route::get('/reset-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showResetForm'])
        ->name('password.reset.form');
    Route::post('/reset-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'resetPassword'])
        ->name('password.reset');
});

// Add these routes for professional password reset
Route::prefix('professional')->name('professional.')->group(function () {
    Route::middleware('guest:professional')->group(function () {
        Route::get('/forgot-password', [App\Http\Controllers\Professional\AuthController::class, 'showForgotForm'])
            ->name('forgot.form');
        Route::post('/forgot-password', [App\Http\Controllers\Professional\AuthController::class, 'sendResetLink'])
            ->middleware(['throttle:5,1,recovery', 'throttle:20,60,recovery-hour'])
            ->name('forgot.send');
        Route::get('/reset-password', [App\Http\Controllers\Professional\AuthController::class, 'showResetForm'])
            ->name('password.reset.form');
        Route::post('/reset-password', [App\Http\Controllers\Professional\AuthController::class, 'resetPassword'])
            ->name('password.reset');
    });
});

Route::middleware(['auth:professional'])->group(function () {
    // Professional billing routes
    Route::get('/billing', [BillingController::class, 'index'])->name('professional.billing.index');
    Route::get('/billing/download-invoice/{booking}', [\App\Http\Controllers\Professional\ProfessionalBillingController::class, 'downloadInvoice'])->name('professional.billing.download-invoice');
});

// Professional payment routes
Route::middleware(['auth:admin'])->group(function () {
    // Existing routes...
    Route::post('/admin/professional/payment/save', [App\Http\Controllers\Admin\BillingController::class, 'savePayment'])
        ->name('admin.professional.payment.save');
    Route::post('/admin/professional/payment/update-status', [App\Http\Controllers\Admin\BillingController::class, 'updatePaymentStatus'])
        ->name('admin.professional.payment.update-status');
});

Route::get('user/terms-and-conditions', function () {
    return response()->file(public_path('pdf/user-terms-and-conditions.pdf'));
})->name('user.terms');
Route::get('professional/terms-and-conditions', function () {
    return response()->file(public_path('pdf/professional-terms-and-conditions.pdf'));
})->name('professional.terms');

Route::get('terms', function () {
    $services = Service::latest()->get();
    return view('frontend.sections.terms', compact('services'));
})->name('terms');

Route::get('/services/search', [HomeController::class, 'searchservice'])->name('services.search');

// Admin review routes
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth:admin']], function () {
    Route::get('reviews', [App\Http\Controllers\Admin\ReviewController::class, 'index'])
        ->name('reviews.index');
    Route::delete('reviews/{review}', [App\Http\Controllers\Admin\ReviewController::class, 'destroy'])
        ->name('reviews.destroy');
});

// Professional review routes
Route::prefix('professional')->middleware(['auth:professional'])->name('professional.')->group(function () {
    Route::get('/reviews', [App\Http\Controllers\Professional\ReviewController::class, 'index'])
        ->name('reviews.index');
});
Route::get('/search-services', [HomeController::class, 'searchServices'])->name('search.services');
Route::middleware(['auth:user'])->group(function () {
    // Legacy compatibility URI; mirrors user.event.booking.success ownership checks.
    Route::get('event-booking/success', [\App\Http\Controllers\Customer\EventController::class, 'bookingSuccess'])
        ->name('event.booking.success');
});

// Help & FAQ Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('help', \App\Http\Controllers\Admin\HelpController::class);
});

// Frontend Help & FAQ Route
Route::get('/help', [\App\Http\Controllers\Admin\HelpController::class, 'frontend'])->name('help.index');
Route::get('/check-auth-status', function () {
    return response()->json([
        'authenticated' => Auth::guard('user')->check()
    ]);
});

Route::post('/force-login', [App\Http\Controllers\Frontend\LoginController::class, 'forceLogin'])->middleware('throttle:5,1')->name('force.login');

Route::middleware(['auth:user', 'check.active.session'])->group(function () {
    Route::get('/user/dashboard', function () {
        return view('user.dashboard');
    })->name('user.dashboard');
});

Route::post('/contact', [ContactFormController::class, 'store'])->name('contact.store');

Route::post('/blog-post/{id}/comment', [App\Http\Controllers\CommentController::class, 'store'])->name('blog.comment.store');

// Comment Management Routes
Route::get('/blog-comments', [App\Http\Controllers\Admin\CommentController::class, 'index'])->name('admin.comments.index');
Route::post('/blog-comments/{id}/approve', [App\Http\Controllers\Admin\CommentController::class, 'approve'])->name('admin.comments.approve');
Route::delete('/blog-comments/{id}', [App\Http\Controllers\Admin\CommentController::class, 'destroy'])->name('admin.comments.destroy');
Route::get('/blog-comments/export', [App\Http\Controllers\Admin\CommentController::class, 'export'])->name('admin.comments.export');



// Add this route for Excel export
Route::get('admin/professional/billing/export-excel', [App\Http\Controllers\Admin\BillingController::class, 'exportBillingToExcel'])
    ->name('admin.professional.billing.export-excel');

// Add this route for Customer billing Excel export
Route::get('admin/customer/billing/export-excel', [App\Http\Controllers\Admin\BillingController::class, 'exportCustomerBillingToExcel'])
    ->name('admin.customer.billing.export-excel');
Route::get('/privacy', function () {
    return view('frontend.sections.privacy');
})->name('privacy');

// Add this in the professional group routes section
Route::get('/professional/rate/session-types', [App\Http\Controllers\Professional\RateController::class, 'getSessionTypes'])->name('professional.rate.get-session-types')->middleware('auth:professional');

// Fallback route - MUST be the last route defined
Route::fallback(function () {
    abort(404);
});
