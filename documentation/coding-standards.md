# easyQuote Coding Standards

The project’s repository contains CS-Fixer config file with the defined rules.
It can be used for automated formatting.<br>
Repository also contains EditorConfig config file with general formatting rules.

## Table of contents

<!-- TOC -->
* [easyQuote Coding Standards](#easyquote-coding-standards)
  * [Table of contents](#table-of-contents)
    * [Class Declaration](#class-declaration)
      * [Class Declaration Rules](#class-declaration-rules)
      * [Methods Order](#methods-order)
      * [Order of Class Elements](#order-of-class-elements)
      * [Grouping Rules](#grouping-rules)
      * [Example Class](#example-class)
    * [Naming Style](#naming-style)
      * [Variable Naming](#variable-naming)
      * [Properties Naming](#properties-naming)
      * [Method Naming](#method-naming)
      * [Function Naming](#function-naming)
      * [Class Naming](#class-naming)
      * [Interface Naming](#interface-naming)
    * [Tabs and Indents](#tabs-and-indents)
      * [Spacing](#spacing)
      * [Vertical Spacing](#vertical-spacing)
      * [Horizontal Spacing](#horizontal-spacing)
    * [Wrapping and Braces](#wrapping-and-braces)
      * [Line Wrapping](#line-wrapping)
      * [Braces placement](#braces-placement)
    * [Comments](#comments)
      * [PHPDoc tags order](#phpdoc-tags-order)
      * [PHPDoc '@param' spaces](#phpdoc-param-spaces)
    * [Coding Style Recommendations](#coding-style-recommendations)
      * [Use Statements](#use-statements)
      * [Function Declaration](#function-declaration)
      * [Strings](#strings)
      * [Predefined Constants](#predefined-constants)
      * [Incrementing](#incrementing)
      * [Array/List Declaration Style](#arraylist-declaration-style)
<!-- TOC -->

### Class Declaration

#### Class Declaration Rules

- Class properties must have `readonly` modifier by default unless they are supposed to be dynamic.
- All classes must be final unless their extension is implied.
- Promoted class properties should be declared on separated lines in constructor.
- All class properties must be explicitly typed.

#### Methods Order

Getters/Setters order: getters first

#### Order of Class Elements

- constants
- used traits
- fields
- constructor
- methods

#### Grouping Rules

- Keep getters and setters together
- Keep dependent methods together (depth-first order)

#### Example Class

```php
final class ExampleOrderService implements ProcessesCrmOrder
{
    const USER_AGENT = 'CRM';

    use LoggerAware;

    public function __construct(
        protected readonly ErrorBag $errorBag,
        protected readonly EventDispatcher $eventDispatcher,
        protected LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function processOrder(OrderData $data): Result
    {
        $this->logger->info('This is a sample log message...');

        try {
            $createdData = $this->createData($data);
        } catch (OrderProcessException $e) {
            $this->errorBag->add('ER-01', $e->getMessage());

            $this->logger->error('Could not save the order data.', [
                'errors' => $this->errorBag->toArray(),
            ]);

            return Result::fail($this->errorBag->toArray());
        }

        $this->eventDispatcher->dispatch(new SomeOrderProcessSucceeded($data);

        return Result::success($createdData->order);
    }
    
    public function setLogger(LoggerInterface $logger): ExampleOrderService
    {
        $this->logger = $logger;

        return $this;
    }
}

```

### Naming Style

#### Variable Naming

Style: camelCase

#### Properties Naming

Style: camelCase

#### Method Naming

Style: camelCase
The name should reflect the action.

#### Function Naming

Style: snake_case
The name should reflect the action.

#### Class Naming

Style: camelCase

| Prefix            | Comment                                                                                                |
|-------------------|--------------------------------------------------------------------------------------------------------|
| \*Data            | Data Transfer Object                                                                                   |
| \*Adapter         | Any class used to make an existing class plug into<br>the interface requirements of another            |
| \*Request         | Implementation of form request                                                                         |
| \*Resolver        | Any class used to resolve<br>concrete result                                                           |
| \*Factory         | Class which constructs objects which implements<br> the interface requirements                         |
| \*Service         | Any class used to process<br>specific data                                                             |
| \*Controller      | Implementation of HTTP controller<br>class                                                             |
| \*Driver          | Implementation of the driver interface                                                                 |
| \*Command         | Implementation of console command<br>class                                                             |
| \*EntityService   | Any class used to mutate data in database                                                              |
| \*Enum            | Implementation of enumeration                                                                          |
| \*Integration     | Any class used to communicate<br>with third-party service                                              |
| \*Serializer      | Class which serializes data in the appropriate format<br>and deserializes data into the concrete type. |
| \*Listener        | Any class used as event-listener                                                                       |
| \*Email           | Simple class for building mail data. Implements Mailable<br>interface.                                 |
| \*Policy          | Classes that organize authorization logic around a particular<br>model or resource.                    |
| \*ServiceProvider | Classes that responsible for registering container bindings<br>and bootstrapping the application.      |
| \*Queries         | Classes that contain prepared queries to the database                                                  |
| \*Settings        | Classes with typed database settings                                                                   |
| \*Seeder          | Any class used as database seeder                                                                      |
| \*Test            | Classes for unit-testing                                                                               |

#### Interface Naming

Style: camelCase
The name of the interfaces must explicitly reflect the behavior.
Adding the prefix `Interface` to the class name is not welcome.

### Tabs and Indents

Indentation should be 4 spaces for each level of indentation.
Indents should not be kept on empty lines.

#### Spacing

#### Vertical Spacing

No more than 1 blank line is allowed in the declarations and in the code. Zero lines after `{` and before `}`.
Single line breaks should be used when declaring promoted properties in the class constructor.
Each file must end with blank line.

**Function Declaration Example**

```
    function good_formatted_func(): void
    {
            // This is a sample comment.
            echo "foo";
            
            echo "bar";
    }
    
    function bad_formatted_func(): void
    {
        
            echo "foo";
            
            
            // This is a sample comment.
            echo "bar";
            
            
    }
```

#### Horizontal Spacing

Spaces before function declaration/call parentheses must not be present.

Space before parentheses must be present in:

- anonymous function parentheses
- array function parentheses
- `if` parentheses
- `for` parentheses
- `while` parentheses
- `switch` parentheses
- `catch` parentheses

Spaces must be present around:

- assignment operators (`=`, `+=`)
- logical operators (`&&`, `||`)
- equality operators (`==`, `!=`, `===`)
- relational operators (`<`, `>`, `<=`, `>=`, `<=>`)
- bitwise operators (`&`, `|`, `^`)
- additive operators (`+`, `-`)
- multiplicative operators (`*`, `/`, `%`, `**`)
- shift operators (`>>`, `<<`)
- null coalescing operator (`??`)

Strings concatenation must not be separated by spaces.

### Wrapping and Braces

#### Line Wrapping

A line of code should not exceed 120 characters and should aim to be less than 100 characters in
most cases.

#### Braces placement

- In namespace: End of line
- In class declaration: Next line
- In anonymous class declaration: End of line
- In function declaration: Next line
- In closure declaration: End of line

### Comments

- Explicitly declared argument types must not be documented with docblock
- Avoid commenting the code which explicitly describes itself
- Align parameter/property names
- Align tag comments

#### PHPDoc tags order

- @param
- @return
- @throws

#### PHPDoc '@param' spaces

Between tag and type: 1
Between type and name: 1
Between name and description: 2

### Coding Style Recommendations

The following are recommendations of best practices to improve readability, reduce refactoring
work required when logic changes, and/or promote reuse, and are strongly encouraged.

#### Use Statements

- Be sure not grouping the `use` statements. Version control system provides better diff when `use` statements placed on
  separate lines.
- `Use` statement should be sorted alphabetically

#### Function Declaration

- Function arguments must be always typed
- Function return types must be always declared

#### Strings

- Single quote unless interpolation is needed

#### Predefined Constants

- True/False/Null constants should be in lower case

#### Incrementing

- Use Left-hand property increment/decrement

#### Array/List Declaration Style

- Short declaration style ([])
- Add a comma after last element in multiline array 
