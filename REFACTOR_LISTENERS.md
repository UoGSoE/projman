# Notification Listener Refactor

**Date:** 2025-11-18
**Context:** Cleaning up legacy notification system after moving from database-driven rules to config-driven approach

---

## The Problem

After refactoring the notification system from a complex database-driven rules engine to a simple config-based approach (see `config/projman.php`), we were left with architectural debt:

### Issues Identified

1. **Massive code duplication** - Three nearly identical listener classes:
   - `ScopingSubmittedListener` (54 lines)
   - `SchedulingSubmittedToDCGGListener` (65 lines)
   - `SchedulingScheduledListener` (54 lines)
   - Each had its own copy of `resolveRecipients()` method (95% identical)

2. **Inconsistent handling patterns** - Some events used:
   - A mega-listener (`ProjectEventsListener` - 117 lines)
   - Dedicated listener classes
   - Orphaned methods that weren't being called (`handleFeasibilityApproved`, `handleFeasibilityRejected`)

3. **No clear architectural pattern** - Confusion about when to use which approach

4. **ProjectEventsListener doing too much:**
   - Generic `handle()` for some events
   - Specific handler methods for others
   - Two different recipient resolution methods
   - Multiple responsibilities mixed together

---

## The Solution

### 1. Created `RoleUserResolver` Service Class

**File:** `app/Services/RoleUserResolver.php`

**Responsibility:** Resolve which users should be notified based on event configuration

**Key Method:**
```php
public function forEvent(object $event): Collection
```

**Why a service class?**
- The logic doesn't belong on User or Role models (not domain behavior)
- Returns User models (not emails) for maximum reusability
- Could be used for reports, audits, or anywhere we need "users by role"
- Single responsibility: orchestrate user resolution from config

**Key Design Decisions:**
- Takes the event object (not explicit config) → encapsulates config lookup
- Throws exception if event config is missing → fail fast on misconfiguration
- Returns empty collection (not null) → valid result when roles have no users
- Handles all special cases: stage-specific roles, project owner inclusion, etc.

### 2. Created Discrete Event Listeners

Following Laravel 12 conventions, each event now has its own dedicated listener:

- `FeasibilityApprovedListener` → `FeasibilityApproved` event
- `FeasibilityRejectedListener` → `FeasibilityRejected` event
- `ProjectCreatedListener` → `ProjectCreated` event
- `ProjectStageChangeListener` → `ProjectStageChange` event

### 3. Refactored Existing Listeners

Updated existing listeners to use the new service:
- `ScopingSubmittedListener` - 54 lines → 17 lines
- `SchedulingScheduledListener` - 54 lines → 17 lines

**Note:** `SchedulingSubmittedToDCGGListener` was handled separately by the user to directly email the DCGG address.

### 4. Deleted Legacy Code

Removed `ProjectEventsListener` entirely (117 lines).

---

## Architectural Decisions

### Decision 1: Service Class vs Trait vs Helper Methods

**Options considered:**
1. Helper methods on Role or User models
2. Trait shared across listeners
3. Service class

**Chosen:** Service class

**Reasoning:**
- Not domain behavior (doesn't belong on Role/User)
- Reusable beyond notifications
- Clear single responsibility
- Easy to test in isolation
- No coupling to models where it doesn't belong

### Decision 2: Throwing Exceptions vs Silent Return

**Scenario:** Listener resolves zero users to notify

**Options considered:**
1. Silently return (assume valid)
2. Log a warning
3. Throw an exception

**Chosen:** Throw descriptive RuntimeException

**Reasoning:**
- We use Sentry.io for exception monitoring
- "No users to notify" is an exceptional circumstance (misconfigured roles, missing seeds)
- Fail fast → immediate Sentry alert → GitHub agent investigates automatically
- Provides full context: event class, project ID, stack trace
- Forces fixing root cause vs hiding the problem

**Key insight:** The distinction between "service returns empty collection" (valid) vs "listener has nobody to email" (exceptional) shows proper separation of concerns.

### Decision 3: Where to Handle Empty Users

**Options considered:**
1. Service throws when finding zero users
2. Listeners throw when needing to send to zero users

**Chosen:** Listeners throw

**Reasoning:**
- Service's job: "resolve users" → empty collection is a valid result
- Service doesn't have business context → doesn't know if empty is exceptional
- Listener's job: "send email" → zero recipients IS exceptional
- Keeps service reusable for other contexts where empty might be valid (reports, audits)
- Business logic belongs in listeners, not infrastructure services

**Analogy:** `User::where('email', 'x')->get()` returning empty is fine, but in a login controller that's exceptional.

### Decision 4: Service Method Signature

**Options considered:**
```php
// Option 1: Explicit parameters
public function resolve(Project $project, array $config): Collection

// Option 2: Event-aware
public function forEvent(object $event): Collection
```

**Chosen:** `forEvent(object $event)`

**Reasoning:**
- Encapsulates config lookup inside service
- Listeners become "dumber" - don't need to know about config structure
- Future-proof: switching to database config only requires changing service
- Cleaner listener code (one line vs two)

---

## Listener Pattern

All listeners now follow this simple pattern:

```php
public function handle(EventClass $event): void
{
    $users = app(RoleUserResolver::class)->forEvent($event);

    if ($users->isEmpty()) {
        throw new \RuntimeException(
            'No recipients found for '.EventClass::class.
            ' notification (Project #'.$event->project->id.')'
        );
    }

    Mail::to($users->pluck('email'))->queue(
        new EventMail($event->project)
    );
}
```

**Benefits:**
- Consistent across all listeners
- Easy to understand at a glance
- Descriptive exceptions for Sentry
- Each listener ~10-17 lines total

---

## Testing Changes

### Updated Test

Changed expectation from "no mail sent" to "exception thrown":

```php
it('throws exception when no users have configured roles', function () {
    // Remove all role assignments
    $this->admin->roles()->detach();
    // ...

    // Fake ProjectCreated event to prevent its listener from throwing
    Event::fake([ProjectCreated::class]);

    $project = Project::factory()->create(['user_id' => $this->projectOwner->id]);

    // Should throw an exception since no users have the required role
    expect(fn () => event(new FeasibilityApproved($project)))
        ->toThrow(\RuntimeException::class, 'No recipients found');
});
```

**All tests passing:** 9 tests, 28 assertions

---

## Benefits Achieved

### Code Reduction
- **Before:** ~400+ lines across multiple listener files
- **After:** ~140 lines total (including service class)
- **Reduction:** ~65% less code

### Architectural Improvements
1. **Single Responsibility Principle** - Each class has one clear job
2. **DRY** - Zero duplication of recipient resolution logic
3. **Consistency** - All listeners follow the same pattern
4. **Laravel Conventions** - Discrete listeners auto-wired by event type
5. **Separation of Concerns** - Service (data) vs Listeners (business logic)
6. **Fail Fast** - Configuration errors caught immediately with full context
7. **Maintainability** - New events require ~17 lines of code
8. **Testability** - Service can be tested independently

### Operational Benefits
- **Sentry Integration** - Descriptive exceptions alert team immediately
- **GitHub Agent** - Can automatically investigate notification failures
- **Reusability** - Service can be used beyond notifications
- **Future-Proof** - Easy to swap config source (file → database) by changing one class

---

## Lessons Learned

### Architecture
1. **Service classes are justified** when logic doesn't belong on models but needs reusability
2. **Business context matters** - What's "exceptional" depends on where you are (service vs listener)
3. **Fail fast with context** - Better than silent failures or generic errors
4. **Separation of concerns** - Service returns data, listeners apply business rules

### Laravel/PHP
1. **Event auto-discovery** - Laravel 12 automatically wires listeners based on `handle()` type hints
2. **Mail::to([])** throws exception - Empty arrays are NOT handled gracefully
3. **Event::fake()** prevents listeners from running during tests

### Testing
1. **Fake events selectively** - `Event::fake([SpecificEvent::class])` prevents just that listener
2. **Test exception messages** - Partial matches ensure descriptive errors
3. **Order matters** - Create projects before removing roles when testing empty scenarios

### Decision-Making Process
1. **Question assumptions** - "Should we really just return?" led to better solution
2. **Consider reusability** - Designing for one use case made service more valuable
3. **Think about failure modes** - How will we know when things go wrong?

---

## Migration Notes

### For Future Events

To add a new event notification:

1. Add configuration to `config/projman.php`:
```php
\\App\\Events\\NewEvent::class => [
    'roles' => ['Role Name'],
    'include_project_owner' => false,
    'mailable' => \\App\\Mail\\NewEventMail::class,
],
```

2. Create the listener (use artisan):
```bash
php artisan make:listener NewEventListener
```

3. Implement the standard pattern:
```php
public function handle(NewEvent $event): void
{
    $users = app(RoleUserResolver::class)->forEvent($event);

    if ($users->isEmpty()) {
        throw new \RuntimeException(
            'No recipients found for '.NewEvent::class.
            ' notification (Project #'.$event->project->id.')'
        );
    }

    Mail::to($users->pluck('email'))->queue(
        new NewEventMail($event->project)
    );
}
```

4. Laravel 12 will auto-wire the listener based on the `handle()` type hint

---

## Related Documentation

- **Previous refactor:** Notification system simplified from database-driven to config-driven (see PROJECT_PLAN.md lines 241-331)
- **Configuration:** See `config/projman.php` for notification routing
- **Testing:** See `tests/Feature/NotificationTest.php` for comprehensive examples
