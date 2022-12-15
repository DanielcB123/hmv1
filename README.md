# A1AMS Multi-tenant SaaS boilerplate


### Requirements

The boilerplate uses Laravel 9. PHP 7.4 and 8.\* are supported.

# Setting it up

These are the steps to get the app up and running. Once you're using the app, feel free to change any individual parts.

> Note: If you don't want to use Nova, remove it like this: https://github.com/tenancy-for-laravel/saas-boilerplate/issues/6. See the *Nova admin panel* section for an explanation why I chose Nova.
>
> Also note that **for production deploys, you will need to have Nova credentials in auth.json**
> 
> And final Note about Nova: the master branch uses Nova 3, **if you want to use Nova 4 see [#92](https://github.com/tenancy-for-laravel/saas-boilerplate/issues/92)**

1. Clone the repository
2. `composer install` (or `composer update` if you see errors related to your PHP version)
3. Copy `.env.example` to `.env` and run `php artisan key:generate`
4. Create a MySQL database. If you want to use a non-root user, make sure that the user has permissions to create other users and access all databases (or change the DB manager used for `mysql` to the one that does not create permissions). Add the database name to your `.env`
5. Start Redis (it's used as the queue driver — feel free to change this to any other *asynchronous* driver)
6. Run migrations: `php artisan migrate`
7. Grab your Stripe test keys: [https://dashboard.stripe.com/dashboard](https://dashboard.stripe.com/dashboard) and add them to `.env` (`STRIPE_KEY` and `STRIPE_SECRET`)
8. Create two products in Stripe: [https://dashboard.stripe.com/products](https://dashboard.stripe.com/products) and copy the price ids to the `config/saas.php` file — see how it looks now, you'll know what to paste where.
9. Configure the app URL & domains
    - If you're using Valet, you can go with the default set-up. Just make sure your project is accessible on `saas.test`
    - If you're using `php artisan serve`, make `localhost` your central & Nova domain (replace the `saas.test` instances with `localhost` in `.env`). Your development tenants will have domains like `foo.localhost` and `bar.localhost`
    - If you're using anything else — the process will be similar to the one to `php artisan serve`.
10. Run the queue worker: `php artisan queue:work`
11. Visit your central domain, e.g. `saas.test`.

# The onboarding flow

When you visit the central domain, you can either register or login as a tenant. This doesn't use Laravel authentication — tenants aren't authenticable users in our setup. Instead, we use a more appropriate & simple implementation.

### Registration

The user enters his details:

- company name
- domain
- full name
- email
- password

We create a Tenant instance and persist the data on the tenant. Next, we generate a route that takes the tenant to his home page and impersonates the first user in the database.

The user visits this route in the browser and is shown a "We're building your application" message.

Meanwhile, a queued job runs in the background. It does the following:

- create the tenant's database
- migrate the database
- create a superuser, with the credentials from the tenant (full name, email, password)
- seeds the database with two sample posts

The job will run pretty much instantly.

The user clicks on the refresh button and if the database is set up already, he'll be taken to the tenant's (sub)domain and logged in as the first user (= the owner of the organization/application/team/whatever is the business term in your case).

Note: The "We're building your application" screen is displayed using the exception handler. In some rare instances, it could make local debugging somewhat more difficult, so be aware of how this works.

Also note that if the queued job runs fast, the user won't even see the "we're building your application" screen. Wonders of modern technology!

And, conversely, if the queued job runs super slow, the user might get a 403. The TTL for the impersonation token is 60 seconds. If your queues are slow for whatever reason, you can increase the TTL.

### Login

A user enters his email address. Based on the email, we find the correct tenant and redirect the user to the tenant's application's login screen with the email filled in.

Note that this only works with the **owner's** email address. Not *any* user's email address. We're not using resource syncing here — we're only keeping track of the organization owners.

# Project structure

### Controllers

- `App\Http\Controllers\Central`
- `App\Http\Controllers\Tenant`

I recommend sticking to this structure

### Routes

Central route names are prefixed with `central.` and tenant routes with `tenant.`.

Again, sticking with this is recommended. The exception would be if your central app is very thin and your tenant app is very big, then you can make your life a bit easier and just prefix central route names and keep tenant routes unprefixed.

### Views

- `central`
    - `central.tenants`
        - `central.tenants.login`
        - `central.tenants.register`
    - `central.landing`
- `errors` (technically part of the tenant app)
    - `errors.building`
- `layouts`
    - `layouts.central`
    - `layouts.tenant`
- `livewire`
    - `livewire.tenant.*`
    - `livewire.central.*` - nothing here yet, but I recommend using a structure like this
- `partials`
- `tenant`
    - `tenant.auth.*` - laravel auth
    - `tenant.posts`
        - `tenant.posts.create`
        - `tenant.posts.index`
        - `tenant.posts.show`
    - `tenant.settings`
        - `tenant.settings.application`
        - `tenant.settings.user`

### Models

There are very few models in the boilerplate so I'm not imposing any custom namespace structure on you (the default `App\Models` namespace is used).

That said, I recommend using:

- `App\Models\Central\*`
- `App\Models\Tenant\*`

Or if it works better for you, you can even go with `App\Central\*` and `App\Tenant\*` (and split controllers that way too).

### Middleware

We're using the `InitializeTenancyByDomainOrSubdomain` middleware. To make life less of a repetitive pain, we created a `tenant` middleware group in `app/Http/Kernel.php`. It includes this tenancy middleware, `'web'` and `PreventAccessFromCentralDomains`.

### Tenancy setup

We use only one central domain. This is so that we can use named routes, generate domains from subdomains, and a few other things. If you **really** need multiple central domains, you may change the implementation, but really consider if you need it. Also note that in our implementation, we read the one central domain from an environment variable. So you can use a different one locally, on your staging server, and on your production server.

Next, we use seeder — `TenantDatabaseSeeder`. Feel free to disable seeding in the TenancyServiceProvider file.

Speaking of seeding and TenancyServiceProvider, there's also a job for creating a superadmin: `CreateTenantAdmin`.

We're using the `PermissionControllerMySQLDatabaseManager`. This means that tenants get their own DB users. Feel free to change this.

# User interface

The frontend is made with server-rendered Blade and Tailwind CSS. In some places, we're using Alpine JS for interactivity.

The point of this is to give you a starting point from which you *can* work. Many people in our community use Tailwind CSS, so it's the best framework for this. Also, while not using Tailwind UI directly, a lot of the pages try to mimic that modern Stripe-ish style a bit. So if you decide to use Tailwind UI, it should fit in well.

# Livewire

Just like we use Alpine for frontend interactivity, we use Livewire for interactive forms that require a round-trip to the server.

I decided to go with Livewire because:

- it's literally Blade and nothing else — everyone who can make a Laravel app can understand Livewire
- using any specific frontend framework would impose a heavy technical decision on you, and changing it would be a pain
- there's no need to write an API, which is also heavy. If you decide you don't want dynamic forms like these and want regular POST forms, you can just copy the code from the Livewire component, copy the view, and make a controller-route-form thing.

# Nova admin panel

You can manage tenants, their domains, and administrators in a Nova admin panel.

Nova can be visited on `/nova`.

If you don't have a Nova license and don't want to purchase one — that's fine. You can write your own admin panel and everything will work just as well.

I would love to have a custom admin panel that would have worked for everyone, but then a lot of people use Nova or other custom admin panels, so there would be no way to integrate the tenant admin panel into their own admin panel.

For that reason, Nova is the imperfect but best solution for providing you with an admin panel.

There are Nova resources prepared for the aforementioned tenants, domains, and administrators. There's also a Nova action for impersonating tenants. Simply open the detail page for the tenant and select the Impersonate action up top.

### Creating an admin

You can create an admin user in the central database using the `php artisan nova:user` command.

# Domains

The package provides us with a lightweight layer for identifying tenants using domains.

In this boilerplate, we took it a step further and implemented the following concepts:

### Primary domains

Each tenant has one primary domain. This domain is used for generating tenant routes. You can use the following helper to generate tenant routes, with beautiful syntax:

```php
$tenant->route('tenant.home');

// Don't forget that you can use tenant() to get the current tenant
tenant()->route('tenant.settings.user');
```

### Fallback domains

Each tenant can add any number of custom third-party domains. He can also make them primary. But in case something goes wrong, we need a fallback domain on which the application will be guaranteed to be accessible.

The fallback domain should be a subdomain on your central domain.

## Domain management UI

In the domain management part of the Application Settings page, you can add custom domains, mark domains as primary, remove domains, and change fallback domains.

Primary domains or fallback domains **cannot** be removed. If you're visiting a fallback domain (we're using a secret trick to check that) and change it, you'll be redirected to the new fallback domain.

# SaaS config file

The `config/saas.php` file stores some config keys related to the SaaS functionality. Currently, it stores the trial length, your Stripe keys (loaded from env), and your subscription plans.

Feel free to add your own config keys to the file.

# Tenant app

The tenant part of the app contains a very slim sample app so that you can see the data separation in action and get a sense of how the tenant app should be structured and tested.

Right now, the tenant app contains controllers, models, migrations, and views for a very simple blog-type application. You can create posts, view an index of posts, and view each post individually.

## Settings

Apart from this blog-type logic, there are also two setting screens. You can navigate to them using the menu shown by clicking your gravatar on the top right corner of the screen.

On the user settings screen, any user can update their name, email, and password.

On the application settings screen, the owner can:

- change the application name
- change the application's domains
- change billing settings

# Authentication

We have two separate authenticable models. One for the central app and one for the tenant app.

In the central app, the `Admin` model is the authenticable user. In tenant app, it's `User`.

This brings the issue of "what model will `auth()->user()` return?". There are two solutions to this problem.

1. Switch the model used by the `web` auth guard. This would basically be something like this:

    ```php
    Event::listen(TenancyBootstrapped::class, function () {
        config(['auth.providers.user.model' => User::class]);
    });

    Event::listen(RevertedToCentralContext::class, function () {
        config(['auth.providers.user.model' => Admin::class]);
    });
    ```

    Though this solution has some flaws with e.g. the guard caching the config, so simply changing the config upon reverting to central context wouldn't probably be enough — more direct manipulation with Laravel auth classes would be needed.

2. Use separate auth guards. This is the cleaner solution in my opinion. It's what the boilerplate uses.If you go to `config/auth.php`, you'll see this:

    ```php
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ```

    We have a `web` (default name) auth guard, for the tenant app. And an `admin` auth guard for the central app.

Now, we don't have any controllers in the central app that would use auth. So in our case, we only told Nova to use the `admin` auth guard and we're done.

If you want to use authentication in central routes, be sure to specify the `admin` auth guard.

Note: The authentication routes are registered in a route group with a `tenant.` name prefix, so you should use `tenant.register`, not `register`.

# Testing

An important part of this boilerplate is the test suite.

There are two types of tests:

1. Central tests. These happen in your central app. They also may create tenants, but not necessarily.
2. Tenant tests. These happen PURELY in your tenant app. They don't make requests to the central app.

For central tests, it's simple. You write tests like you're used to. If you need to create a tenant in the tests, you may use `$this->createTenant()` or the factory or create the model directly. Then you can `tenancy()->initialize($tenant)` if you need to. Though keep in mind that you should probably `tenancy()->end()` too so that the test cleanup happens properly.

For tenant tests, we do some nice magic.

- We create a tenant initialize tenancy
- We force the app URL to that tenant's URL
- We set the `SERVER_NAME` and `HTTP_HOST` server variables to that tenant's domain (`tenant.localhost`)
- We log in as the superadmin (feel free to remove this part, it's in `TenantTestCase`)

The magical thing about forcing the URLs and hostnames is that you can use:

```php
$this->get('posts')->assertSee('foo');
```

**as if tenancy didn't exist at all!** You don't have to think about it for one second.

Normally, you'd have to make a request to `http://tenant.localhost/posts`. While that's fine once in a while, having to think of that constantly would be a pain and your tests would be a mess.

Also, tests use `localhost` as the central domain. This is set in `phpunit.xml`'s env. The reason for this is that changing the central domains config in `setUp()` doesn't work — the config is read before `setUp()` is executed to register the central routes.

Finally, one thing to keep in mind: The tests use your central connection with the `saas_test` database. This is in `phpunit.xml`. Be sure to create that database.

Tenants' test databases are created as MySQL databases (and users).

# Billing

Cashier Stripe is used for billing.

A user may configure billing on the Application Settings screen.

The user first needs to add a payment card, then he can select a subscription, and then he can download invoices. He will be shown the upcoming payment along with the current state of his subscription.

By default, 14 days of trial are used. See the SaaS config section of this document.

The code related to billing is located in Livewire components included in the `tenant/settings/application` view. Another part is in the `CreateTenantAction` where we create the tenant as a Stripe customer.

The billing is relatively lightweight and even if you need to change most of the implementation (e.g. migrate to Mollie or Paddle) it should be pretty straightforward.

One thing to keep in mind is that we made some changes to Cashier migrations (so that we can use the `Tenant` model) and disabled them in AppServiceProvider.

## Invoices

We use Stripe invoices because we store customer info (billing address etc) on Stripe, so that's the most straightforward way of generating invoices.

If you'd like to use the Cashier way of generating invoices, feel free to change this.

## Ploi integration - Production set-up & HTTPS certificates

### Subdomains

Say your central domain is `acme.com`. You want to add an alias for `*.acme.com` in the site's aliases (Manage tab on the left).

Then, you want to open the SSL tab and create a certificate for both `acme.com` and `*.acme.com`:
```
acme.com,*.acme.com
```

You will need to use a supported DNS provider for the wildcard certificate. I recommend Cloudflare, it's free.

### Ploi - Customer domains & tenant DB management

**If you haven't set up a Ploi site before, watch this video: [Deploying SaaS boilerplate to Ploi](https://youtu.be/FUm5vnHnZNQ)**

> Note that **for production deploys, you will need to have Nova credentials in auth.json**

Open your `.env` file and set the Ploi environment variables. You can read the server & site from the URL in your Ploi dashboard. In my case:
> /panel/servers/**9690**/sites/**19533**

So `PLOI_SERVER=9690` and `PLOI_SITE=19533`.

Your `PLOI_TOKEN` can be generated here: https://ploi.io/profile/api-keys

If you set this up, the integration will work as follows:
1. when you add a custom domain, Ploi will be listening to that domain on port 80
2. when you click *Request certificate*, Ploi will generate a certificate and start listening on 443 as well
3. when you click *Revoke certificate*, the previous step will be reversed

When customers add their own domains, you want them to add a `CNAME` or `ALIAS` record pointing either at your central domain or at their fallback subdomain. I recommend playing with Netlify (it's free) and adding your own domain to a project. You'll see the process from the user's perspective.

## Updating

Since this is an application template and not a package, there's no straightforward way to update it.

That said, updates won't really be "needed". We recommend that you join the GH repo, Watch it, and then see new commits as they're added and if you like the added stuff, you add it to your project too.

See [this GitHub issue](https://github.com/tenancy-for-laravel/saas-boilerplate/issues/23) for info about using multiple git remotes to pull new commits.
