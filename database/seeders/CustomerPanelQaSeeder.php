<?php

namespace Database\Seeders;

use App\Models\AdditionalService;
use App\Models\AllEvent;
use App\Models\Booking;
use App\Models\BookingTimedate;
use App\Models\CustomerProfile;
use App\Models\Professional;
use App\Models\ProfessionalService;
use App\Models\Service;
use App\Models\ServiceMCQ;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Local-only synthetic fixtures for authenticated Customer Panel UI QA.
 * Does not create real payment transactions or send mail.
 */
class CustomerPanelQaSeeder extends Seeder
{
    public const CUSTOMER_EMAIL = 'qa.customer@yuktione.local';
    public const OTHER_EMAIL = 'qa.other@yuktione.local';
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        DB::transaction(function () {
            $service = Service::query()->firstOrCreate(
                ['slug' => 'qa-career-guidance'],
                [
                    'name' => 'QA Career Guidance',
                    'status' => 'active',
                    'meta_title' => 'QA Career Guidance',
                    'meta_description' => 'Synthetic service for Customer Panel QA',
                ]
            );

            $mcq = ServiceMCQ::query()->firstOrCreate(
                [
                    'service_id' => $service->id,
                    'question' => 'What is your primary QA goal?',
                ],
                [
                    'answer1' => 'Growth',
                    'answer2' => 'Clarity',
                    'answer3' => 'Confidence',
                    'answer4' => 'Support',
                ]
            );

            $professional = Professional::query()->firstOrCreate(
                ['email' => 'qa.pro@yuktione.local'],
                [
                    'name' => 'QA Professional',
                    'password' => bcrypt('Password123!'),
                    'phone' => '9876543210',
                    'status' => 'accepted',
                    'active' => 1,
                ]
            );

            ProfessionalService::query()->firstOrCreate(
                [
                    'professional_id' => $professional->id,
                    'service_id' => $service->id,
                ],
                [
                    'service_name' => $service->name,
                    'duration' => 30,
                    'description' => 'Synthetic professional service for QA',
                    'features' => ['Assessment', 'Guidance'],
                    'tags' => 'qa,career',
                    'requirements' => 'None',
                ]
            );

            $customer = User::query()->firstOrCreate(
                ['email' => self::CUSTOMER_EMAIL],
                [
                    'name' => 'QA Customer',
                    'phone' => '9123456780',
                    'password' => self::PASSWORD,
                    'email_verified' => true,
                    'registration_completed' => true,
                    'registration_status' => 'completed',
                    'password_set_at' => now(),
                    'customer_onboarding_completed_at' => now(),
                ]
            );

            $other = User::query()->firstOrCreate(
                ['email' => self::OTHER_EMAIL],
                [
                    'name' => 'QA Other Customer',
                    'phone' => '9123456781',
                    'password' => self::PASSWORD,
                    'email_verified' => true,
                    'registration_completed' => true,
                    'registration_status' => 'completed',
                    'password_set_at' => now(),
                    'customer_onboarding_completed_at' => now(),
                ]
            );

            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $customer->id],
                [
                    'name' => 'QA Customer',
                    'email' => self::CUSTOMER_EMAIL,
                    'phone' => '9123456780',
                    'address' => '12 QA Street',
                    'city' => 'Bengaluru',
                    'state' => 'Karnataka',
                    'zip_code' => '560001',
                    'notes' => 'Synthetic QA profile',
                ]
            );

            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $other->id],
                [
                    'name' => 'QA Other Customer',
                    'email' => self::OTHER_EMAIL,
                    'phone' => '9123456781',
                    'city' => 'Chennai',
                    'state' => 'Tamil Nadu',
                ]
            );

            $ownBooking = $this->booking($customer, $professional, $service, 'QA Own Career Consult', now()->addDays(2)->toDateString());
            $this->booking($other, $professional, $service, 'QA Other Private Consult', now()->addDays(3)->toDateString());

            BookingTimedate::query()->firstOrCreate(
                [
                    'booking_id' => $ownBooking->id,
                    'date' => now()->addDays(2)->toDateString(),
                    'time_slot' => '10:00 AM - 10:30 AM',
                ],
                ['status' => 'pending']
            );

            AdditionalService::query()->firstOrCreate(
                [
                    'user_id' => $customer->id,
                    'booking_id' => $ownBooking->id,
                    'service_name' => 'QA Document Review',
                ],
                [
                    'professional_id' => $professional->id,
                    'reason' => 'Synthetic additional service for Customer Panel QA',
                    'base_price' => 500,
                    'cgst' => 45,
                    'sgst' => 45,
                    'igst' => 0,
                    'total_price' => 590,
                    'professional_status' => 'approved',
                    'admin_status' => 'approved',
                    'user_status' => 'pending',
                    'consulting_status' => 'in_progress',
                    'payment_status' => 'pending',
                    'negotiation_status' => 'none',
                    'payment_method' => null,
                ]
            );

            if (Schema::hasTable('booking_chats')) {
                DB::table('booking_chats')->updateOrInsert(
                    [
                        'booking_id' => $ownBooking->id,
                        'sender_id' => $customer->id,
                        'sender_type' => 'customer',
                        'message' => 'QA synthetic chat message',
                    ],
                    [
                        'is_read' => false,
                        'is_system_message' => false,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            AllEvent::query()->firstOrCreate(
                ['heading' => 'QA Local Event'],
                [
                    'card_image' => 'qa-event.jpg',
                    'date' => now()->addWeek()->toDateString(),
                    'mini_heading' => 'QA',
                    'short_description' => 'Synthetic event for booking summary fixture',
                    'starting_fees' => 299,
                    'status' => 'approved',
                    'created_by_type' => 'admin',
                ]
            );

            $this->command?->info('CustomerPanelQaSeeder complete');
            $this->command?->info('Login: ' . self::CUSTOMER_EMAIL . ' / ' . self::PASSWORD);
            $this->command?->info('Own booking id: ' . $ownBooking->id);
            $this->command?->info('Service id: ' . $service->id . ' MCQ id: ' . $mcq->id);
        });
    }

    private function booking(User $user, Professional $professional, Service $service, string $name, string $date): Booking
    {
        $existing = Booking::query()
            ->where('user_id', $user->id)
            ->where('service_name', $name)
            ->first();

        if ($existing) {
            return $existing;
        }

        $attrs = [
            'user_id' => $user->id,
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'service_name' => $name,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'plan_type' => 'one_time',
            'session_type' => 'online',
            'month' => now()->format('F Y'),
            'days' => '1',
            'booking_date' => $date,
            'time_slot' => '10:00 AM - 10:30 AM',
            'amount' => 1000,
            'base_amount' => 847.46,
            'cgst_amount' => 76.27,
            'sgst_amount' => 76.27,
            'igst_amount' => 0,
            'payment_status' => 'paid',
            'paid_status' => 'paid',
            'status' => 'confirmed',
            'payment_method' => 'qa_fixture',
            'transaction_id' => 'QA-TXN-' . uniqid(),
        ];

        return Booking::query()->create($attrs);
    }
}
