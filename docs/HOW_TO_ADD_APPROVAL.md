# How to Add Approval to Any Module

This project uses [wezlo/filament-approval](https://github.com/mustafakhaleddev/filament-approval)
as the approval engine. Follow these 4 steps to wire approval into any new module.

---

## Step 1 — Add `HasApprovalWorkflow` to the Model

```php
use App\Traits\HasApprovalWorkflow;

class PurchaseOrder extends Model
{
    use HasApprovalWorkflow;
    // ...
}
```

This exposes:
- `submitForApproval()` — send to the approval engine
- `isApproved()` / `isPendingApproval()` / `isRejected()` — status checks
- `approvalStatusLabel()` — bilingual human-readable label
- `canBePosted()` — returns `true` only when fully approved
- `guardApprovalBeforePosting()` — call this inside any "post" action to enforce approval

---

## Step 2 — Add `HasApprovalActions` to the ViewRecord Page

```php
use App\Filament\Traits\HasApprovalActions;

class ViewPurchaseOrder extends ViewRecord
{
    use HasApprovalActions;

    protected function getHeaderActions(): array
    {
        return array_merge(parent::getHeaderActions(), $this->getApprovalActions());
    }
}
```

This adds 5 context-aware header actions automatically:
**Submit for Approval**, **Approve**, **Reject**, **Comment**, **Delegate**

---

## Step 3 — Add `ApprovalStatusColumn` to the Resource table

```php
use Wezlo\FilamentApproval\Columns\ApprovalStatusColumn;

public static function table(Table $table): Table
{
    $table = parent::table($table);

    return $table->pushColumns([
        ApprovalStatusColumn::make()->toggleable(isToggledHiddenByDefault: true),
    ]);
}
```

---

## Step 4 — Add `ApprovalsRelationManager` to the Resource relations

```php
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

public static function getRelationManagers(): array
{
    return array_merge(parent::getRelationManagers(), [
        ApprovalsRelationManager::class,
    ]);
}
```

Optionally add the infolist section (shows current approval status inline):

```php
use Wezlo\FilamentApproval\Infolists\ApprovalStatusSection;

public static function infolist(Schema $schema): Schema
{
    $schema = parent::infolist($schema);

    $prop = new \ReflectionProperty(get_class($schema), 'components');
    $prop->setAccessible(true);
    $existing = $prop->getValue($schema);

    return $schema->components([
        ...(is_array($existing) ? $existing : []),
        ApprovalStatusSection::make(),
    ]);
}
```

---

## Step 5 — Create an Approval Flow in the Admin Panel

Go to **Admin → Approvals → Approval Flows → Create**.

Set:
- **Model**: the fully-qualified class name of your model (e.g. `App\Models\PurchaseOrder`)
- **Steps**: add one step per approver level, choose **Role** resolver and enter the role name

---

## Complete Example — PurchaseOrder

### Model (`app/Models/PurchaseOrder.php`)

```php
<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasApprovalWorkflow;

    protected $fillable = ['title', 'amount', 'status'];

    public function confirm(): void
    {
        $this->guardApprovalBeforePosting();

        $this->update(['status' => 'confirmed']);
    }
}
```

### Resource (`app/Filament/Resources/PurchaseOrderResource.php`)

```php
<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Wezlo\FilamentApproval\Columns\ApprovalStatusColumn;
use Wezlo\FilamentApproval\Infolists\ApprovalStatusSection;
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

class PurchaseOrderResource extends Resource
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ... your columns ...
                ApprovalStatusColumn::make()->toggleable(isToggledHiddenByDefault: true),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            ApprovalsRelationManager::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            // ... your sections ...
            ApprovalStatusSection::make(),
        ]);
    }
}
```

### View Page (`app/Filament/Resources/PurchaseOrderResource/Pages/ViewPurchaseOrder.php`)

```php
<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Traits\HasApprovalActions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    use HasApprovalActions;

    protected function getHeaderActions(): array
    {
        return array_merge(parent::getHeaderActions(), $this->getApprovalActions());
    }
}
```
