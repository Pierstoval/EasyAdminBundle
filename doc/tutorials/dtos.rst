Using DTOs in EasyAdmin
=======================

DTOs (Data Transfer Object) are considered a good practice by many developers as a way to
decouple your database data (the entities) from your domain logic.

This is especially true when using the Symfony Form component, because
it updates the entity's data and therefore can lead to an invalid state.
As invalid states can be the source of inconsistencies, database corruption,
if one single ``$entityManager->flush()`` is forgotten in the code, EasyAdmin
provides a simple way to configure DTOs with Symfony forms.

Introduction to DTOs
--------------------

Let's consider a ``User`` entity:

.. code-block:: php

    /**
     * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
     */
    class User implements UserInterface
    {
        /**
         * @ORM\Id()
         * @ORM\GeneratedValue()
         * @ORM\Column(type="integer")
         */
        private $id;

        /**
         * @ORM\Column(type="string", length=180, unique=true)
         */
        private $email;

        /**
         * @ORM\Column(type="json")
         */
        private $roles = [];

        /**
         * @var string The hashed password
         * @ORM\Column(type="string")
         */
        private $password;

        // ...
    }

As Doctrine does not use any getter nor setter to hydrate our objects, we do not
need any.

If we use DTOs, our entity will be populated by the DTO itself when submitting
the form, so all we need are entrypoint methods in our Entity for this.

We call these **static constructors** (or sometimes "named constructors") and
**mutators**.

Here is an example of such methods for our ``User`` entity:

.. code-block:: php

    class User implements UserInterface
    {
        // ...

        public static function createFromAdmin(NewUserAdminDTO $dto): self
        {
            $obj = new self();

            $obj->username = $dto->getUsername();
            // ...

            return $obj;
        }

        public function updateFromAdmin(ExistingUserAdminDTO $dto): void
        {
            // ...
            $this->username = $dto->getUsername();
        }
    }

Here, we have a nice example of a **static constructor** and a **mutator**,
both using an object called a DTO.

Create your first DTO
---------------------

The goal of the DTO is to replace the Entity in the form. This way, we will
only manipulate a plain old PHP object (POPO) that will carry some data.
This plain object is the actual DTO. We will use it to transfer its data
into our entity, data that will come from the Form submission, and that can
be validated with the Symfony Validator if it is enabled.

Here is an example of the ``new`` DTO:

.. code-block:: php

    namespace App\Form\DTO;

    class NewUserAdminDTO
    {
        private $email;
        private $plainPassword;

        public function getEmail(): ?string
        {
            return $this->email;
        }

        public function setEmail(?string $email): void
        {
            $this->email = $email;
        }

        public function getPlainPassword(): ?string
        {
            return $this->plainPassword;
        }

        public function setPlainPassword(?string $plainPassword): void
        {
            $this->plainPassword = $plainPassword;
        }
    }

This DTO is here to represent the data that will be sent to a potential
"new user" form.

We can have a similar DTO for a "update user" form:

.. code-block:: php

    namespace App\Form\DTO;

    class ExistingUserAdminDTO
    {
        private $email;
        private $resetPassword;

        public static function fromUser(User $user): self
        {
            $new = new self();

            $new->email = $user->getEmail();

            return $new;
        }

        public function getEmail(): ?string
        {
            return $this->email;
        }

        public function setEmail(?string $email): void
        {
            $this->email = $email;
        }

        public function getResetPassword(): ?bool
        {
            return $this->resetPassword;
        }

        public function setResetPassword(?bool $resetPassword)
        {
            $this->resetPassword = $resetPassword;
        }
    }

As you can see here, we even have a **static constructor** in our DTO. Of
course: when editing a User, we need default data! That's what this constructor
is for.

Configuring EasyAdmin to use our DTOs
-------------------------------------

EasyAdmin provides automatic setting up for DTOs with a few configuration
options.

According to the examples above, here are the fields you should add to tell
EasyAdmin to use your DTOs:

.. code-block:: yaml

    easy_admin:
        entities:
            User:
                class: App\Entity\User

                new:
                    dto_class: App\Form\DTO\NewUserAdminDTO
                    dto_entity_method: createFromAdmin
                    # Default DTO factory is the native constructor, so we don't specify it here.

                edit:
                    dto_class: App\Form\DTO\ExistingUserAdminDTO
                    dto_factory: fromUser
                    dto_entity_method: updateFromAdmin

                    # You define fields as the DTO fields instead of the Entity one.
                    fields:
                        - email
                        - property: resetPassword
                          type: checkbox

And *voilà*! Nothing more to do, EasyAdmin will use your configuration to create
your DTOs in the right situation, and create or update your entities properly.

DTO configuration options
-------------------------

* ``dto_class``: This is the first thing you have to define if you want to use
  DTOs. It will tell EasyAdmin to separate the DTO (that will be injected in the
  form) and the Entity (that will be used for persist & flush calls on the ORM).
* ``dto_factory``: This option can be of three types:
  * ``null`` (default) will use the native constructor, leading to code like
    ``$dto = new $dtoClass()``. You could also explicitly set ``__construct`` as value,
    which leads to the exact same behavior but is more explicit.
  * The **static method name** that will be used to create the DTO, like
    ``$dtoClass:$dtoFactory()``.
  * A **static factory** from another class like ``'MyDTOFactory::createDTO'``.
  * The name of an object factory registered as a service (see below an example of object
    factory).
* ``dto_entity_method``: This is the **method** that will be used by EasyAdmin
  when the form is **submitted and valid**, on the **entity**. This can execute
  instructions like ``$entity->$method($dto);``. This method is mandatory if you
  want to use DTOs.

Create custom object factories
------------------------------

Thanks to the ``EasyCorp\Bundle\EasyAdminBundle\Form\DTO\ObjectFactoryInterface``, you can
create services that will create your DTOs.

Here is an example of such factory:

.. code-block:: php

    <?php

    use EasyCorp\Bundle\EasyAdminBundle\Form\DTO\ObjectFactoryInterface;

    class CustomObjectFactory implements ObjectFactoryInterface
    {
        public function getName(): string
        {
            return 'custom_factory';
        }

        public function createDTO(string $class, string $view, $defaultData = null)
        {
            // Your logic to create the DTO.
        }
    }

Used with a configuration similar to this:

.. code-block:: yaml

    easy_admin:
        entities:
            User:
                class: App\Entity\User

                edit:
                    # Here, the custom factory is used, and the name is the return of CustomObjectFactory::getName().
                    dto_factory: custom_factory
                    dto_class: App\Form\DTO\ExistingUserAdminDTO
