# Resolvers!

> The inverse of a transformer.

- Immutable
- Type Safe
- Unexpected input is ignored
- Lazily evaluated
- Recursive / Nested
- Not aware of Laravel / Eloquent
- Collection agnostic
- Supports dot-notation

## Install

```
composer require figured/resolvers
```

## Example

```php
/* Example resolver implementation */
class UserResolver extends Resolver implements UserInterface
{
    public function getId(): ?int
    {
        return $this->get("id", Type::INTEGER | Type::NULL);
    }
    
    public function getName(): string
    {
        return $this->get("name", Type::STRING);
    }
    
    public function getOrganisation(): OrganisationInterface
    {
        return $this->hasOne("org", OrganisationResolver::class);
    }
    
    /** @return FarmInterface[] */
    public function getFarms(): array
    {
        return $this->hasMany("farms", FarmResolver::class);
    }
}

/* Create a new user from input, such as a request. */
$user = new UserResolver([
    "id" => 1,
    "name" => "Ann Perkins",
    "org" => [
       ...
    ],
    "farms" => [
        [...],
        [...],
    ],
]);

/* This should then become a common pattern. */
$user = UserResolver::resolve([/* data */]);

/* Do something with the user's farms. */
foreach ($user->getFarms() as $farm) {
    
}

/* Example of a price resolved as a decimal */
class SaleResolver implements SaleInterface
{
    public function getPrice(): Decimal
    {
        return new Decimal($this->get("price", Type::STRING | Type::INTEGER), 16);
    }
}

/* Example of a price resolved as a date */
class SaleResolver
{
    public function getSaleDate(): Carbon
    {
        return new Carbon($this->get("sale_date", Type::STRING));
    }
}
```

## The problem we are trying to solve...

We are trying to **map an array** (such as POST data, or test data) **to a domain entity interface**.

For example, turn:

```php
$cropping->name          = $croppingData['name'];
$cropping->crop_type_id  = $croppingData['crop_type'];
$cropping->opening       = $croppingData['opening'] ?? 0;
$cropping->opening_stock = $croppingData['opening_stock'] ?? 0;
$cropping->opening_value = $croppingData['opening_value'] ?? 0;
$cropping->scenario_id   = $this->scenario->id;
```

Into:

```
$cropping = CroppingResolver::of($croppingData)->with([
    "scenario_id" => $this->scenario->id,
]);
```

---

Here is an example from the scenario cropping manager, which might not be the best example but it illustrates a real scenario.

Things to look out for:
- Direct array access with hard-coded string indices
- Checking things like "is new" based on the ID.
- Many `??` to guard against missing data / bad input. (Risky!)
- Cleaning the input using `array_only`.

```php
public function save(array $data): ScenarioCropping
{
    /** @var ScenarioCropping $cropping */
    $isNew = false;
    $id    = array_get($data, 'id');
    if ($id) {
        $cropping = ScenarioCropping::findOrFail($id);
    } else {
        $cropping = new ScenarioCropping();
        $isNew    = true;
    }

    $croppingData = array_only($data, [
        'name',
        'crop_type',
        'opening',
        'opening_stock',
        'opening_value',
    ]);

    $cropping->name          = $croppingData['name'];
    $cropping->crop_type_id  = $croppingData['crop_type'];
    $cropping->opening       = $croppingData['opening'] ?? 0;
    $cropping->opening_stock = $croppingData['opening_stock'] ?? 0;
    $cropping->opening_value = $croppingData['opening_value'] ?? 0;
    $cropping->scenario_id   = $this->scenario->id;
    $cropping->fill($croppingData);
    
    if ($isNew) {
        $cropType                       = $cropping->cropType;
        $cropping->account_id           = $this->createCroppingAccount($cropType, $cropping->name);
        $cropping->stock_account_id     = $this->createCroppingInventoryAccount($cropType, $cropping->name);
        $cropping->valuation_account_id = $this->createCroppingValuationAccount($cropType, $cropping->name);
    } else {
        $this->renameAccount($cropping);
    }

    $cropping->save();
}

protected function renameAccount(ScenarioCropping $scenarioCropping)
{
    $account = XeroAccount::where([
        'accountid' => $scenarioCropping->account_id,
        'source'    => AccountsContract::SOURCE_SCENARIO,
        'source_id' => $this->scenario->id,
    ]);

    if ($account->exists()) {
        $accountName = $this->generateAccountName($scenarioCropping->cropType, $scenarioCropping->name);

        $account->update([
            'name' => $accountName,
        ]);
    }
}
```

Using resolvers, we can avoid:

- Direct, hard-coded array access
- Not knowing what keys the array has to offer
- Painful refactoring when keys or models change.

```php
public function save(ScenarioCroppingInterface $input): ScenarioCroppingInterface
{
    $isNew = $input->getId() === null;
    
    $model = $isNew ? new ScenarioCropping() : ScenarioCropping::findOrFail($input->getId());
    
    $attrs = [
        "name"          => $input->getName(),
        "crop_type_id"  => $input->getCropTypeId(),        
        "opening"       => $input->isOpening(),    
        "opening_stock" => $input->getOpeningStock(),        
        "opening_value" => $input->getOpeningValue(),        
        "scenario_id"   => $input->getScenarioId(),      
    ]; 
    
    $cropType = CropType::findOrFail($input->getCropTypeId());
    
    if (!$isNew) {
        $this->renameAccount($cropType, $input->getName());
        
    } else {
        $attrs += [
            "account_id"           => $this->createCroppingAccount($cropType, $input->getName());
            "stock_account_id"     => $this->createCroppingInventoryAccount($cropType, $input->getName());
            "valuation_account_id" => $this->createCroppingValuationAccount($cropType, $input->getName());
        ];
    }
    
    $model->fill($attrs);
    $model->save();
    
    return $model;
}

protected function renameAccount(ScenarioCroppingInterface $cropping, CropType $cropType)
{
    $account = XeroAccount::where([
        'accountid' => $cropping->getAccountId(),
        'source_id' => $cropping->getScenarioId(),
        'source'    => AccountsContract::SOURCE_SCENARIO,
    ]);

    if ($account->exists()) {
        $account->update([
            'name' => $this->generateAccountName($scenarioCropping->cropType, $cropping->getName()),
        ]);
    }
}
```

---
