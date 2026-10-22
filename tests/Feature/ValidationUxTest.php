<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\ValidationErrorId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ValidationUxTest extends TestCase
{
    use RefreshDatabase;

    private function shareErrors(string $bag = 'default', array $messages = []): ViewErrorBag
    {
        $bags = new ViewErrorBag;
        $bags->put($bag, new MessageBag($messages));

        View::share('errors', $bags);

        return $bags;
    }

    private function checkoutContext(): array
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'price' => 25.00,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);

        return [$user, $product];
    }

    private function createAddress(User $user, string $type = 'shipping', array $overrides = []): Address
    {
        return Address::create(array_merge([
            'user_id' => $user->id,
            'address_type' => $type,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'address_line1' => '123 Main St',
            'address_line2' => 'Apt 4B',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip' => '90001',
            'country' => 'US',
        ], $overrides));
    }

    /*
    |--------------------------------------------------------------------------
    | Shared error summary component
    |--------------------------------------------------------------------------
    */

    public function test_error_summary_renders_validation_messages_when_errors_exist(): void
    {
        $this->shareErrors('default', ['email' => ['The email field is required.']]);

        $html = Blade::render('<x-error-summary />');

        $this->assertStringContainsString('data-error-summary', $html);
        $this->assertStringContainsString('The email field is required.', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('aria-labelledby="error-summary-heading"', $html);
        $this->assertStringContainsString('id="error-summary-heading"', $html);
    }

    public function test_error_summary_does_not_render_when_there_are_no_errors(): void
    {
        View::share('errors', new ViewErrorBag);

        $html = Blade::render('<x-error-summary />');

        $this->assertStringNotContainsString('data-error-summary', $html);
        $this->assertStringNotContainsString('Please correct the following', $html);
    }

    public function test_error_summary_renders_only_the_requested_named_bag(): void
    {
        $bags = new ViewErrorBag;
        $bags->put('default', new MessageBag(['email' => ['Default bag error.']]));
        $bags->put('updatePassword', new MessageBag(['password' => ['Update password bag error.']]));

        View::share('errors', $bags);

        $html = Blade::render('<x-error-summary bag="updatePassword" id="profile-password-errors" />');

        $this->assertStringContainsString('Update password bag error.', $html);
        $this->assertStringNotContainsString('Default bag error.', $html);
        $this->assertStringContainsString('data-error-bag="updatePassword"', $html);
        $this->assertStringContainsString('id="profile-password-errors"', $html);
    }

    public function test_error_summary_escapes_validation_messages(): void
    {
        $this->shareErrors('default', ['email' => ['<script>alert(1)</script>']]);

        $html = Blade::render('<x-error-summary />');

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /*
    |--------------------------------------------------------------------------
    | Input level accessibility
    |--------------------------------------------------------------------------
    */

    public function test_text_input_sets_aria_invalid_and_describedby_when_field_has_error(): void
    {
        $this->shareErrors('default', ['email' => ['The email field is required.']]);

        $html = Blade::render('<x-text-input name="email" type="email" />');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="email-error"', $html);
        $this->assertStringContainsString('name="email"', $html);
    }

    public function test_text_input_preserves_existing_aria_describedby_references(): void
    {
        $this->shareErrors('default', ['email' => ['Invalid email.']]);

        $html = Blade::render('<x-text-input name="email" type="email" aria-describedby="email-help" />');

        $this->assertStringContainsString('aria-describedby="email-help email-error"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
    }

    public function test_text_input_does_not_set_aria_invalid_when_field_has_no_error(): void
    {
        $this->shareErrors('default', ['other' => ['Unrelated.']]);

        $html = Blade::render('<x-text-input name="email" type="email" />');

        $this->assertStringNotContainsString('aria-invalid', $html);
        $this->assertStringNotContainsString('aria-describedby', $html);
    }

    public function test_text_input_uses_named_error_bag_when_provided(): void
    {
        $bags = new ViewErrorBag;
        $bags->put('default', new MessageBag(['password' => ['Default bag error.']]));
        $bags->put('updatePassword', new MessageBag(['password' => ['New password is invalid.']]));

        View::share('errors', $bags);

        $html = Blade::render('<x-text-input name="password" type="password" error-bag="updatePassword" />');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="update_password-password-error"', $html);
    }

    public function test_input_error_renders_stable_id_for_field_association(): void
    {
        $html = Blade::render(
            '<x-input-error :messages="$messages" field="email" />',
            ['messages' => ['The email field is required.']]
        );

        $this->assertStringContainsString('id="email-error"', $html);
        $this->assertStringContainsString('data-error-for="email"', $html);
        $this->assertStringContainsString('The email field is required.', $html);
    }

    public function test_input_error_without_field_renders_no_identifier(): void
    {
        $html = Blade::render(
            '<x-input-error :messages="$messages" />',
            ['messages' => ['The email field is required.']]
        );

        $this->assertStringNotContainsString('id="email-error"', $html);
        $this->assertStringNotContainsString('data-error-for', $html);
        $this->assertStringContainsString('The email field is required.', $html);
    }

    public function test_input_label_for_attribute_matches_control_id(): void
    {
        $html = Blade::render('<x-input-label for="email" value="Email address" />');

        $this->assertStringContainsString('for="email"', $html);
        $this->assertStringContainsString('Email address', $html);
    }

    /*
    |--------------------------------------------------------------------------
    | Guest layout (authentication) forms
    |--------------------------------------------------------------------------
    */

    public function test_login_page_shows_error_summary_after_failed_validation(): void
    {
        $this
            ->from('/login')
            ->post('/login', [
                'email' => '',
                'password' => '',
            ]);

        $page = $this->get('/login');
        $page->assertOk();
        $html = $page->getContent();

        $this->assertStringContainsString('data-error-summary', $html);
        $this->assertStringContainsString('The email field is required.', $html);
        $this->assertStringContainsString('The password field is required.', $html);
    }

    public function test_login_page_does_not_render_error_summary_without_errors(): void
    {
        $page = $this->get('/login');
        $page->assertOk();

        $this->assertStringNotContainsString('data-error-summary', $page->getContent());
    }

    public function test_login_invalid_fields_expose_aria_invalid(): void
    {
        $this
            ->from('/login')
            ->post('/login', [
                'email' => '',
                'password' => '',
            ]);

        $page = $this->get('/login');
        $page->assertOk();
        $html = $page->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*id="email"[^>]*aria-invalid="true"/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*id="password"[^>]*aria-invalid="true"/', $html);
        $this->assertStringContainsString('aria-describedby="email-error"', $html);
        $this->assertStringContainsString('aria-describedby="password-error"', $html);
        $this->assertStringContainsString('id="email-error"', $html);
        $this->assertStringContainsString('id="password-error"', $html);
    }

    public function test_login_labels_remain_associated_with_controls(): void
    {
        $page = $this->get('/login');
        $page->assertOk();
        $html = $page->getContent();

        $this->assertStringContainsString('for="email"', $html);
        $this->assertStringContainsString('id="email"', $html);
        $this->assertStringContainsString('for="password"', $html);
        $this->assertStringContainsString('id="password"', $html);
    }

    public function test_register_page_shows_error_summary_and_does_not_repopulate_passwords(): void
    {
        $this
            ->from('/register')
            ->post('/register', [
                'name' => '',
                'lastname' => '',
                'email' => 'not-an-email',
                'phone' => '',
                'role_type' => 'customer',
                'password' => 'Secret123!',
                'password_confirmation' => 'Mismatch123!',
            ]);

        $page = $this->get('/register');
        $page->assertOk();
        $html = $page->getContent();

        $this->assertStringContainsString('data-error-summary', $html);
        $this->assertStringContainsString('The email field must be a valid email address.', $html);
        $this->assertStringContainsString('confirmation does not match', $html);

        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="password"[^>]*value="[^"]+"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="password_confirmation"[^>]*value="[^"]+"/', $html);
        $this->assertStringNotContainsString('Secret123!', $html);
        $this->assertStringNotContainsString('Mismatch123!', $html);
    }

    public function test_guest_layout_error_summary_links_to_the_failing_field(): void
    {
        $user = User::factory()->create();

        $this
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $this->assertGuest();

        $page = $this->get('/login');
        $page->assertOk();
        $html = $page->getContent();

        $this->assertStringContainsString('data-error-summary', $html);
        $this->assertStringContainsString('These credentials do not match our records', $html);
        $this->assertStringContainsString('href="#email"', $html);
    }

    /*
    |--------------------------------------------------------------------------
    | Named error bags
    |--------------------------------------------------------------------------
    */

    public function test_login_failure_uses_the_default_error_bag(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_profile_password_form_uses_named_bag_errors_for_aria_invalid(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'NewSecret123!',
                'password_confirmation' => 'NewSecret123!',
            ]);

        $page = $this->actingAs($user)->get('/profile');
        $page->assertOk();
        $html = $page->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*id="update_password_current_password"[^>]*aria-invalid="true"/', $html);
        $this->assertStringContainsString('aria-describedby="update_password-current_password-error"', $html);
        $this->assertStringContainsString('id="update_password-current_password-error"', $html);
        $this->assertStringContainsString('data-error-bag="updatePassword"', $html);
        $this->assertStringNotContainsString('data-error-bag="default"', $html);

        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="current_password"[^>]*value="[^"]+"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="password"[^>]*type="password"[^>]*value="[^"]+"/', $html);
    }

    public function test_profile_password_update_uses_the_update_password_error_bag(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'NewSecret123!',
                'password_confirmation' => 'NewSecret123!',
            ]);

        $response->assertSessionHasErrorsIn('updatePassword', ['current_password']);
        $response->assertSessionDoesntHaveErrors(['current_password']);
    }

    public function test_profile_delete_modal_uses_user_deletion_bag_for_aria_invalid(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $page = $this->actingAs($user)->get('/profile');
        $page->assertOk();
        $html = $page->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*id="password"[^>]*aria-invalid="true"/', $html);
        $this->assertStringContainsString('aria-describedby="user_deletion-password-error"', $html);
        $this->assertStringContainsString('id="user_deletion-password-error"', $html);
        $this->assertStringContainsString('data-error-bag="userDeletion"', $html);
    }

    public function test_named_error_bags_do_not_emit_duplicate_error_ids_on_the_same_page(): void
    {
        $user = User::factory()->create();

        // A failing update-password request writes to the "updatePassword" bag.
        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        // A failing delete-account request writes to the "userDeletion" bag.
        // Both forms live on /profile and both validate a field called
        // "password", so their error elements must not share a DOM id.
        $this->actingAs($user)->from('/profile')->delete('/profile', [
            'password' => 'wrong-password',
        ]);

        $page = $this->actingAs($user)->get('/profile');
        $page->assertOk();
        $html = $page->getContent();

        preg_match_all('/\sid="([^"]+)"/', $html, $matches);
        $ids = $matches[1];
        $duplicates = array_values(array_diff_assoc($ids, array_unique($ids)));

        $this->assertSame([], $duplicates, 'Rendered page must not contain duplicate element ids.');
        $this->assertStringContainsString('id="update_password-password-error"', $html);
        $this->assertStringContainsString('id="user_deletion-password-error"', $html);

        // Each input must reference its own form's error element.
        $this->assertStringContainsString(
            'name="password" type="password" autocomplete="new-password" aria-invalid="true" aria-describedby="update_password-password-error"',
            $html
        );
        $this->assertStringContainsString(
            'name="password" type="password" placeholder="Password" aria-invalid="true" aria-describedby="user_deletion-password-error"',
            $html
        );
    }

    public function test_validation_error_id_is_scoped_by_error_bag(): void
    {
        $this->assertSame('email-error', ValidationErrorId::for('email'));
        $this->assertSame('email-error', ValidationErrorId::for('email', 'default'));
        $this->assertSame('update_password-password-error', ValidationErrorId::for('password', 'updatePassword'));
        $this->assertSame('user_deletion-password-error', ValidationErrorId::for('password', 'userDeletion'));
        $this->assertNotSame(
            ValidationErrorId::for('password', 'updatePassword'),
            ValidationErrorId::for('password', 'userDeletion')
        );
    }

    public function test_input_error_and_text_input_agree_on_the_error_id_for_the_default_bag(): void
    {
        $this->shareErrors('default', ['email' => ['The email field is required.']]);

        $html = Blade::render(
            '<x-text-input name="email" type="email" /><x-input-error :messages="$messages" field="email" />',
            ['messages' => ['The email field is required.']]
        );

        $this->assertStringContainsString('aria-describedby="email-error"', $html);
        $this->assertStringContainsString('id="email-error"', $html);
    }

    public function test_input_error_and_text_input_agree_on_the_error_id_for_a_named_bag(): void
    {
        $this->shareErrors('updatePassword', ['password' => ['The new password is invalid.']]);

        $html = Blade::render(
            '<x-text-input name="password" type="password" error-bag="updatePassword" />'
                .'<x-input-error :messages="$messages" field="password" bag="updatePassword" />',
            ['messages' => ['The new password is invalid.']]
        );

        $this->assertStringContainsString('aria-describedby="update_password-password-error"', $html);
        $this->assertStringContainsString('id="update_password-password-error"', $html);
    }

    public function test_tested_forms_have_no_dangling_aria_describedby_references(): void
    {
        $htmls = [];

        // Login (default error bag)
        $this->from('/login')->post('/login', ['email' => '', 'password' => '']);
        $htmls['login'] = $this->get('/login')->getContent();

        // Register (default error bag)
        $this->from('/register')->post('/register', [
            'name' => '',
            'lastname' => '',
            'email' => 'not-an-email',
            'phone' => '',
            'role_type' => 'customer',
            'password' => 'Secret123!',
            'password_confirmation' => 'Mismatch123!',
        ]);
        $htmls['register'] = $this->get('/register')->getContent();

        // Contact (default error bag)
        $this->from('/contact')->post('/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'phone' => '',
            'subject' => '',
            'message' => '',
        ]);
        $htmls['contact'] = $this->get('/contact')->getContent();

        // Profile (named error bags)
        $user = User::factory()->create();
        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'NewSecret123!',
            'password_confirmation' => 'NewSecret123!',
        ]);
        $htmls['profile'] = $this->actingAs($user)->get('/profile')->getContent();

        foreach ($htmls as $form => $html) {
            preg_match_all('/\sid="([^"]+)"/', $html, $idMatches);
            $ids = $idMatches[1];

            $duplicates = array_values(array_diff_assoc($ids, array_unique($ids)));
            $this->assertSame([], $duplicates, "The {$form} form rendered duplicate element ids.");

            preg_match_all('/aria-describedby="([^"]+)"/', $html, $describedByMatches);
            foreach ($describedByMatches[1] as $reference) {
                foreach (preg_split('/\s+/', trim($reference)) as $target) {
                    if ($target === '') {
                        continue;
                    }
                    $this->assertContains(
                        $target,
                        $ids,
                        "The {$form} form has a dangling aria-describedby reference to \"{$target}\"."
                    );
                }
            }
        }
    }

    public function test_profile_delete_uses_the_user_deletion_error_bag(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response->assertSessionHasErrorsIn('userDeletion', 'password');
    }

    /*
    |--------------------------------------------------------------------------
    | Checkout compatibility
    |--------------------------------------------------------------------------
    */

    public function test_checkout_keeps_single_phase_a_error_summary_without_duplicate(): void
    {
        [$user] = $this->checkoutContext();
        $shipping = $this->createAddress($user);

        $this
            ->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'shipping_address_id' => $shipping->id,
                'payment_method' => 'cod',
                'billing_same' => '0',
            ]);

        $page = $this->actingAs($user)->get(route('checkout.index'));
        $page->assertOk();
        $html = $page->getContent();

        $this->assertSame(
            0,
            substr_count($html, 'data-error-summary'),
            'Checkout must not render the shared error summary on top of the Phase A feedback.'
        );
        $this->assertSame(
            1,
            substr_count($html, 'Please correct the following'),
            'Checkout must keep exactly one Phase A validation summary.'
        );
    }

    public function test_checkout_validation_failure_returns_errors(): void
    {
        [$user] = $this->checkoutContext();
        $shipping = $this->createAddress($user);

        $response = $this
            ->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'shipping_address_id' => $shipping->id,
                'payment_method' => 'cod',
                'billing_same' => '0',
            ]);

        $response->assertSessionHasErrors(['billing_first_name', 'billing_phone']);
    }
}