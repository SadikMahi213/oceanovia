<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Tests\TestCase;

class CheckoutAccessibilityTest extends TestCase
{
    use RefreshDatabase;

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

        $address = Address::create([
            'user_id' => $user->id,
            'address_type' => 'shipping',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'address_line1' => '123 Main St',
            'address_line2' => 'Apt 4B',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip' => '90001',
            'country' => 'US',
        ]);

        return [$user, $product, $address];
    }

    private function submitInvalidCheckout(User $user, Address $address): void
    {
        $this
            ->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'shipping_address_id' => $address->id,
                'payment_method' => 'cod',
                'billing_same' => '0',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Task A - checkout billing label / control associations
    |--------------------------------------------------------------------------
    */

    public function test_checkout_billing_labels_are_associated_with_their_controls(): void
    {
        [$user] = $this->checkoutContext();

        $page = $this->actingAs($user)->get(route('checkout.index'));
        $page->assertOk();
        $html = $page->getContent();

        foreach ($this->billingFields() as $field) {
            $this->assertStringContainsString(
                'for="'.$field.'"',
                $html,
                "The {$field} label must reference its control."
            );
            $this->assertStringContainsString(
                'id="'.$field.'"',
                $html,
                "The {$field} control must expose a stable id."
            );
        }
    }

    public function test_checkout_billing_controls_keep_their_request_field_names(): void
    {
        [$user] = $this->checkoutContext();

        $html = $this->actingAs($user)->get(route('checkout.index'))->getContent();

        foreach ($this->billingFields() as $field) {
            $this->assertStringContainsString(
                'name="'.$field.'"',
                $html,
                "The {$field} request field name must not change."
            );
        }
    }

    public function test_checkout_billing_control_ids_are_unique(): void
    {
        [$user] = $this->checkoutContext();

        $html = $this->actingAs($user)->get(route('checkout.index'))->getContent();

        preg_match_all('/\sid="([^"]+)"/', $html, $matches);
        $ids = $matches[1];
        $duplicates = array_values(array_diff_assoc($ids, array_unique($ids)));

        $this->assertSame([], $duplicates, 'Checkout must not render duplicate element ids.');
    }

    public function test_checkout_billing_labels_are_present_in_both_billing_modes(): void
    {
        [$user] = $this->checkoutContext();

        // The billing fieldset is always rendered; Alpine only toggles
        // visibility and the disabled state. The label/control pairing must
        // therefore be identical in both modes.
        $default = $this->actingAs($user)->get(route('checkout.index'))->getContent();

        [$user, , $address] = $this->checkoutContext();
        $this->submitInvalidCheckout($user, $address);
        $withErrors = $this->actingAs($user)->get(route('checkout.index'))->getContent();

        foreach ($this->billingFields() as $field) {
            $this->assertStringContainsString('for="'.$field.'"', $default);
            $this->assertStringContainsString('for="'.$field.'"', $withErrors);
        }
    }

    public function test_checkout_still_renders_exactly_one_validation_summary(): void
    {
        [$user, , $address] = $this->checkoutContext();

        $this->submitInvalidCheckout($user, $address);

        $html = $this->actingAs($user)->get(route('checkout.index'))->getContent();

        $this->assertSame(1, substr_count($html, 'Please correct the following'));
        $this->assertSame(0, substr_count($html, 'data-error-summary'));
    }

    /*
    |--------------------------------------------------------------------------
    | Task B - the background cart XHR must not swallow validation errors
    |--------------------------------------------------------------------------
    */

    public function test_invalid_checkout_submission_produces_validation_errors(): void
    {
        [$user, , $address] = $this->checkoutContext();

        $response = $this
            ->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'shipping_address_id' => $address->id,
                'payment_method' => 'cod',
                'billing_same' => '0',
            ]);

        $response->assertSessionHasErrors(['billing_first_name', 'billing_phone']);
    }

    public function test_cart_load_does_not_consume_flashed_checkout_validation_errors(): void
    {
        [$user, , $address] = $this->checkoutContext();

        $this->submitInvalidCheckout($user, $address);

        // The one-shot /cart/load XHR fires on the next page load.
        $cartLoad = $this->actingAs($user)->get(route('cart.load'));
        $cartLoad->assertOk();

        // The checkout page must still show the validation summary.
        $page = $this->actingAs($user)->get(route('checkout.index'));
        $page->assertOk();

        $this->assertStringContainsString(
            'Please correct the following',
            $page->getContent(),
            'The background cart XHR must not swallow the flashed checkout validation errors.'
        );
    }

    public function test_cart_load_still_returns_the_user_cart_items(): void
    {
        [$user, $product] = $this->checkoutContext();

        $response = $this->actingAs($user)->get(route('cart.load'));

        $response->assertOk();
        $response->assertJsonStructure(['items']);
        $response->assertJsonPath('items.0.id', $product->id);
        $response->assertJsonPath('items.0.quantity', 1);
    }

    public function test_cart_load_does_not_depend_on_the_shared_validation_errors(): void
    {
        [$user, $product, $address] = $this->checkoutContext();

        $this->submitInvalidCheckout($user, $address);

        // The cart XHR is a JSON endpoint: it must work without the error
        // sharing middleware, which proves the preservation of the flashed
        // errors is not a side effect of that middleware. (The flash itself
        // is aged by Session\Store::save() at the end of every session-using
        // request, not by ShareErrorsFromSession.)
        $response = $this
            ->withoutMiddleware(ShareErrorsFromSession::class)
            ->actingAs($user)
            ->get(route('cart.load'));

        $response->assertOk();
        $response->assertJsonPath('items.0.id', $product->id);
        $response->assertJsonPath('items.0.quantity', 1);
    }

    /*
    |--------------------------------------------------------------------------
    | Shipping address label / control associations
    |--------------------------------------------------------------------------
    */

    public function test_checkout_shipping_labels_are_associated_with_their_controls(): void
    {
        [$user] = $this->checkoutContext();

        $page = $this->actingAs($user)->get(route('checkout.index'));
        $page->assertOk();
        $html = $page->getContent();

        foreach ($this->shippingFields() as $field) {
            $this->assertStringContainsString(
                'for="'.$field.'"',
                $html,
                "The {$field} label must reference its control."
            );
            $this->assertStringContainsString(
                'id="'.$field.'"',
                $html,
                "The {$field} control must expose a stable id."
            );
        }
    }

    public function test_checkout_shipping_label_for_resolves_to_exactly_one_control(): void
    {
        [$user] = $this->checkoutContext();

        $html = $this->actingAs($user)->get(route('checkout.index'))->getContent();

        foreach ($this->shippingFields() as $field) {
            $this->assertSame(
                1,
                substr_count($html, 'for="'.$field.'"'),
                "The {$field} label must reference exactly one control."
            );
            $this->assertSame(
                1,
                preg_match_all('/\sid="'.preg_quote($field, '/').'"/', $html),
                "The {$field} id must exist exactly once on the page."
            );
        }
    }

    public function test_checkout_shipping_controls_keep_their_request_field_names(): void
    {
        [$user] = $this->checkoutContext();

        $html = $this->actingAs($user)->get(route('checkout.index'))->getContent();

        foreach ($this->shippingFields() as $field) {
            $this->assertStringContainsString(
                'name="'.$field.'"',
                $html,
                "The {$field} request field name must not change."
            );
        }
    }

    public function test_checkout_shipping_controls_are_not_statically_disabled(): void
    {
        [$user] = $this->checkoutContext();

        $html = $this->actingAs($user)->get(route('checkout.index'))->getContent();

        // The shipping fieldset is toggled by Alpine (:disabled="!showNewShipping"),
        // so the controls must not carry a static disabled attribute.
        $this->assertStringContainsString(':disabled="!showNewShipping"', $html);

        foreach ($this->shippingFields() as $field) {
            $this->assertDoesNotMatchRegularExpression(
                '/<[^>]*\sid="'.preg_quote($field, '/').'"[^>]*\sdisabled/',
                $html,
                "The {$field} control must not be statically disabled."
            );
        }
    }

    public function test_checkout_shipping_labels_survive_the_new_address_toggle_markup(): void
    {
        [$user, , $address] = $this->checkoutContext();

        // The shipping fieldset is always rendered; Alpine only toggles its
        // visibility and disabled state, so the label/control pairing must be
        // identical whether a saved address is used or a new one is entered.
        $withSavedAddress = $this->actingAs($user)->get(route('checkout.index'))->getContent();

        $this->submitInvalidCheckout($user, $address);
        $withErrors = $this->actingAs($user)->get(route('checkout.index'))->getContent();

        foreach ($this->shippingFields() as $field) {
            $this->assertStringContainsString('for="'.$field.'"', $withSavedAddress);
            $this->assertStringContainsString('for="'.$field.'"', $withErrors);
        }
    }

    /**
     * @return array<int, string>
     */
    private function shippingFields(): array
    {
        return [
            'shipping_first_name',
            'shipping_last_name',
            'shipping_address_line1',
            'shipping_address_line2',
            'shipping_phone',
            'shipping_city',
            'shipping_state',
            'shipping_zip',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function billingFields(): array
    {
        return [
            'billing_first_name',
            'billing_last_name',
            'billing_phone',
            'billing_country',
            'billing_street',
            'billing_apt',
            'billing_city',
            'billing_state',
            'billing_zip',
        ];
    }
}
