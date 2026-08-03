<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('state_code', 2);
            $table->decimal('rate', 5, 4);
            $table->string('name', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('state_code');
        });

        DB::table('tax_rates')->insert([
            ['state_code' => 'AL', 'rate' => 0.0920, 'name' => 'Alabama'],
            ['state_code' => 'AK', 'rate' => 0.0176, 'name' => 'Alaska'],
            ['state_code' => 'AZ', 'rate' => 0.0840, 'name' => 'Arizona'],
            ['state_code' => 'AR', 'rate' => 0.0951, 'name' => 'Arkansas'],
            ['state_code' => 'CA', 'rate' => 0.0882, 'name' => 'California'],
            ['state_code' => 'CO', 'rate' => 0.0772, 'name' => 'Colorado'],
            ['state_code' => 'CT', 'rate' => 0.0635, 'name' => 'Connecticut'],
            ['state_code' => 'DE', 'rate' => 0.0000, 'name' => 'Delaware'],
            ['state_code' => 'FL', 'rate' => 0.0701, 'name' => 'Florida'],
            ['state_code' => 'GA', 'rate' => 0.0731, 'name' => 'Georgia'],
            ['state_code' => 'HI', 'rate' => 0.0441, 'name' => 'Hawaii'],
            ['state_code' => 'ID', 'rate' => 0.0603, 'name' => 'Idaho'],
            ['state_code' => 'IL', 'rate' => 0.0875, 'name' => 'Illinois'],
            ['state_code' => 'IN', 'rate' => 0.0700, 'name' => 'Indiana'],
            ['state_code' => 'IA', 'rate' => 0.0682, 'name' => 'Iowa'],
            ['state_code' => 'KS', 'rate' => 0.0857, 'name' => 'Kansas'],
            ['state_code' => 'KY', 'rate' => 0.0600, 'name' => 'Kentucky'],
            ['state_code' => 'LA', 'rate' => 0.0955, 'name' => 'Louisiana'],
            ['state_code' => 'ME', 'rate' => 0.0550, 'name' => 'Maine'],
            ['state_code' => 'MD', 'rate' => 0.0600, 'name' => 'Maryland'],
            ['state_code' => 'MA', 'rate' => 0.0625, 'name' => 'Massachusetts'],
            ['state_code' => 'MI', 'rate' => 0.0600, 'name' => 'Michigan'],
            ['state_code' => 'MN', 'rate' => 0.0788, 'name' => 'Minnesota'],
            ['state_code' => 'MS', 'rate' => 0.0707, 'name' => 'Mississippi'],
            ['state_code' => 'MO', 'rate' => 0.0816, 'name' => 'Missouri'],
            ['state_code' => 'MT', 'rate' => 0.0000, 'name' => 'Montana'],
            ['state_code' => 'NE', 'rate' => 0.0694, 'name' => 'Nebraska'],
            ['state_code' => 'NV', 'rate' => 0.0823, 'name' => 'Nevada'],
            ['state_code' => 'NH', 'rate' => 0.0000, 'name' => 'New Hampshire'],
            ['state_code' => 'NJ', 'rate' => 0.0666, 'name' => 'New Jersey'],
            ['state_code' => 'NM', 'rate' => 0.0786, 'name' => 'New Mexico'],
            ['state_code' => 'NY', 'rate' => 0.0852, 'name' => 'New York'],
            ['state_code' => 'NC', 'rate' => 0.0698, 'name' => 'North Carolina'],
            ['state_code' => 'ND', 'rate' => 0.0686, 'name' => 'North Dakota'],
            ['state_code' => 'OH', 'rate' => 0.0723, 'name' => 'Ohio'],
            ['state_code' => 'OK', 'rate' => 0.0895, 'name' => 'Oklahoma'],
            ['state_code' => 'OR', 'rate' => 0.0000, 'name' => 'Oregon'],
            ['state_code' => 'PA', 'rate' => 0.0634, 'name' => 'Pennsylvania'],
            ['state_code' => 'RI', 'rate' => 0.0700, 'name' => 'Rhode Island'],
            ['state_code' => 'SC', 'rate' => 0.0744, 'name' => 'South Carolina'],
            ['state_code' => 'SD', 'rate' => 0.0640, 'name' => 'South Dakota'],
            ['state_code' => 'TN', 'rate' => 0.0955, 'name' => 'Tennessee'],
            ['state_code' => 'TX', 'rate' => 0.0820, 'name' => 'Texas'],
            ['state_code' => 'UT', 'rate' => 0.0719, 'name' => 'Utah'],
            ['state_code' => 'VT', 'rate' => 0.0624, 'name' => 'Vermont'],
            ['state_code' => 'VA', 'rate' => 0.0575, 'name' => 'Virginia'],
            ['state_code' => 'WA', 'rate' => 0.0899, 'name' => 'Washington'],
            ['state_code' => 'WV', 'rate' => 0.0650, 'name' => 'West Virginia'],
            ['state_code' => 'WI', 'rate' => 0.0543, 'name' => 'Wisconsin'],
            ['state_code' => 'WY', 'rate' => 0.0536, 'name' => 'Wyoming'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
