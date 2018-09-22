<?php

namespace Illuminate\Tests\Container;

use stdClass;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;

class ContainerTest extends TestCase
{

    /**
     * 容器是一个单例模型
     */
    public function testContainerSingleton()
    {
        // 设置新容器
        $container = Container::setInstance(new Container);

        // 获取的新实例与设置的容器是同一个对象
        $this->assertSame($container, Container::getInstance());

        // 置空容器对象
        Container::setInstance(null);

        // 获取新的容器对象
        $container2 = Container::getInstance();

        // 新的容器对象还是一个 Container 类的实例
        $this->assertInstanceOf(Container::class, $container2);
        // 但新容器和旧容器不是同一个对象
        $this->assertNotSame($container, $container2);
    }

    /**
     * 闭包解析
     */
    public function testClosureResolution()
    {
        // 获取容器
        $container = new Container;
        // 使用 bind 注册 name 服务为一个闭包，该闭包返回 'Taylor'
        $container->bind('name', function () {
            return 'Taylor';
        });
        // 使用 make 解析 name 服务的结果与预期一致
        $this->assertEquals('Taylor', $container->make('name'));
    }

    /**
     * 假如服务已经注册，bindIf 不会再次注册
     */
    public function testBindIfDoesntRegisterIfServiceAlreadyRegistered()
    {
        $container = new Container;
        // 使用 bind 注册 name 服务为一个闭包，该闭包返回 'Taylor'
        $container->bind('name', function () {
            return 'Taylor';
        });
        // 使用 bindIf 注册 name 服务为一个闭包，该闭包返回 'Dayle'
        $container->bindIf('name', function () {
            return 'Dayle';
        });

        // 因为 name 服务已经注册，所以使用 make 解析 name 服务的结果还是 'Taylor'
        $this->assertEquals('Taylor', $container->make('name'));
    }

    /**
     * 假如服务尚未注册，bindIf 会注册成功
     */
    public function testBindIfDoesRegisterIfServiceNotRegisteredYet()
    {
        $container = new Container;
        // 使用 bind 注册 surname 服务为一个闭包，该闭包返回 'Taylor'
        $container->bind('surname', function () {
            return 'Taylor';
        });
        // 使用 bindIf 注册 name 服务为一个闭包，该闭包返回 'Dayle'
        $container->bindIf('name', function () {
            return 'Dayle';
        });

        // 因为 name 服务从未注册，所以使用 make 解析 name 服务的结果是 'Dayle'
        $this->assertEquals('Dayle', $container->make('name'));
    }

    /**
     * 共享闭包解析——注册单例服务
     */
    public function testSharedClosureResolution()
    {
        $container = new Container;
        // 创建标准类对象
        $class = new stdClass;
        // 使用 singleton 注册 class 服务为一个闭包，该闭包返回标准类对象 $class
        $container->singleton('class', function () use ($class) {
            return $class;
        });
        // 使用 make 解析 class 服务的结果和标准类对象是同一个对象
        $this->assertSame($class, $container->make('class'));
    }

    /**
     * 自动对象解析
     */
    public function testAutoConcreteResolution()
    {
        $container = new Container;
        // 使用 make 解析 Illuminate\Tests\Container\ContainerConcreteStub 服务的结果为
        // 'Illuminate\Tests\Container\ContainerConcreteStub' 类的对象
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $container->make('Illuminate\Tests\Container\ContainerConcreteStub'));
    }

    /**
     * 共享对象解析——解析出单例对象
     */
    public function testSharedConcreteResolution()
    {
        $container = new Container;
        // 使用 singleton 注册 Illuminate\Tests\Container\ContainerConcreteStub 服务为单例对象
        $container->singleton('Illuminate\Tests\Container\ContainerConcreteStub');

        $var1 = $container->make('Illuminate\Tests\Container\ContainerConcreteStub');
        $var2 = $container->make('Illuminate\Tests\Container\ContainerConcreteStub');
        // 使用 make 解析 Illuminate\Tests\Container\ContainerConcreteStub 服务获得的两个结果是同一个对象
        $this->assertSame($var1, $var2);
    }

    /**
     * 依赖解析
     */
    public function testAbstractToConcreteResolution()
    {
        $container = new Container;
        // 使用 bind 注册 Illuminate\Tests\Container\IContainerContractStub 接口服务为
        // Illuminate\Tests\Container\ContainerImplementationStub 接口实现类
        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');

        // Illuminate\Tests\Container\ContainerDependentStub 类构造函数依赖
        // Illuminate\Tests\Container\IContainerContractStub 接口
        $class = $container->make('Illuminate\Tests\Container\ContainerDependentStub');
        // 使用 make 解析 Illuminate\Tests\Container\ContainerDependentStub 类服务结果为 $class 对象
        // $class 对象的 impl 属性是 Illuminate\Tests\Container\ContainerImplementationStub 类的实例
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $class->impl);
    }

    /**
     * 递归依赖解析
     */
    public function testNestedDependencyResolution()
    {
        $container = new Container;
        // 使用 bind 注册 Illuminate\Tests\Container\IContainerContractStub 接口服务为
        // Illuminate\Tests\Container\ContainerImplementationStub 接口实现类
        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');

        // Illuminate\Tests\Container\ContainerNestedDependentStub 类构造函数依赖
        // Illuminate\Tests\Container\ContainerDependentStub 类;
        // Illuminate\Tests\Container\ContainerDependentStub 类构造函数依赖
        // Illuminate\Tests\Container\IContainerContractStub 接口
        $class = $container->make('Illuminate\Tests\Container\ContainerNestedDependentStub');

        // 使用 make 解析 Illuminate\Tests\Container\ContainerNestedDependentStub 类服务结果为 $class 对象
        // $class 对象的 inner 属性是 Illuminate\Tests\Container\ContainerDependentStub 类的实例
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerDependentStub', $class->inner);

        // $class 对象的 inner 属性 的 impl 属性是 Illuminate\Tests\Container\ContainerImplementationStub 类的实例
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $class->inner->impl);
    }

    /**
     * 容器对象会传给闭包
     */
    public function testContainerIsPassedToResolvers()
    {
        $container = new Container;
        // 使用 bind 注册 something 服务为一个闭包，该闭包返回自身的第一个参数 $c
        $container->bind('something', function ($c) {
            return $c;
        });
        // 使用 make 解析 something 服务的结果为 $c
        $c = $container->make('something');
        // $c 就是闭包返回的其自身的第一个参数，也就是由容器传递过来的容器对象
        $this->assertSame($c, $container);
    }

    /**
     * 容器可以使用数组形式访问
     * 因为容器实现了 php 的 ArrayAccess 接口
     */
    public function testArrayAccess()
    {
        $container = new Container;
        // 使用数组方式注册 something 服务为一个闭包
        $container['something'] = function () {
            return 'foo';
        };
        // something 服务是容器的数组变量
        $this->assertTrue(isset($container['something']));
        // 使用数组方式 解析 something 服务的结果与预期一致
        $this->assertEquals('foo', $container['something']);
        // 取消容器的 something 服务变量
        unset($container['something']);
        // 则无法再访问容器的 something 服务变量
        $this->assertFalse(isset($container['something']));
    }

    /**
     * 解析（递归）别名服务到正确的结果
     */
    public function testAliases()
    {
        $container = new Container;
        // 使用数组方式注册 foo 服务为一个字符串 'bar'
        $container['foo'] = 'bar';
        // 创建 foo 服务别名为 baz
        $container->alias('foo', 'baz');
        // 创建 baz 服务别名为 bat
        $container->alias('baz', 'bat');
        // 使用 make 解析 foo 服务获取的结果为字符串 'bar'
        $this->assertEquals('bar', $container->make('foo'));
        // 使用 make 解析 baz 服务获取的结果为字符串 'bar'
        $this->assertEquals('bar', $container->make('baz'));
        // 使用 make 解析 bat 服务获取的结果为字符串 'bar'
        $this->assertEquals('bar', $container->make('bat'));
    }

    /**
     * 解析带数组参数的别名服务
     */
    public function testAliasesWithArrayOfParameters()
    {
        $container = new Container;
        // 使用 bind 注册 foo 服务为闭包，该闭包返回其第二个参数
        $container->bind('foo', function ($app, $config) {
            return $config;
        });
        // 创建 foo 服务别名为 baz
        $container->alias('foo', 'baz');
        // 使用 make 解析 baz服务（带数组参数 [1, 2, 3]）的结果与预期一致
        $this->assertEquals([1, 2, 3], $container->make('baz', [1, 2, 3]));
    }

    /**
     * 绑定可以被覆盖
     */
    public function testBindingsCanBeOverridden()
    {
        $container = new Container;
        // 使用数组方式注册 foo 服务为一个字符串 'bar'
        $container['foo'] = 'bar';
        // 使用数组方式注册 foo 服务为一个字符串 'baz'
        $container['foo'] = 'baz';
        // 使用数组方式 解析 foo 服务的结果是第二次注册的字符串 'baz' 而不是第一次注册的字符串 'bar'
        $this->assertEquals('baz', $container['foo']);
    }

    /**
     * 扩展绑定
     */
    public function testExtendedBindings()
    {
        $container = new Container;
        $container['foo'] = 'foo';
        // 使用 extend 重新注册 foo 服务到一个闭包，该闭包扩展了 foo 服务的解析结果
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });
        // 使用 make 解析 foo 服务的结果和预期一致
        $this->assertEquals('foobar', $container->make('foo'));

        $container = new Container;

        // 单例方式注册也支持扩展绑定功能

        // 使用 singleton 注册 foo 服务到一个闭包，该闭包返回一个对象
        $container->singleton('foo', function () {
            return (object) ['name' => 'taylor'];
        });
        // 使用 extend 重新注册 foo 服务到一个闭包，该闭包扩展了 foo 服务的解析结果
        $container->extend('foo', function ($old, $container) {
            $old->age = 26;

            return $old;
        });

        $result = $container->make('foo');

        // 使用 make 解析 foo 服务的结果和预期一致
        $this->assertEquals('taylor', $result->name);
        $this->assertEquals(26, $result->age);
        $this->assertSame($result, $container->make('foo'));
    }

    /**
     * 多次扩展
     */
    public function testMultipleExtends()
    {
        $container = new Container;
        $container['foo'] = 'foo';
        // 使用 extend 重新注册 foo 服务到一个闭包，该闭包扩展了 foo 服务的解析结果
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });
        // 再次使用 extend 重新注册 foo 服务到一个闭包，该闭包扩展了 foo 服务的解析结果
        $container->extend('foo', function ($old, $container) {
            return $old.'baz';
        });

        // 使用 make 解析 foo 服务的结果和预期一致
        $this->assertEquals('foobarbaz', $container->make('foo'));
    }

    /**
     * instance 注册服务后，返回注册时的实例
     */
    public function testBindingAnInstanceReturnsTheInstance()
    {
        $container = new Container;

        $bound = new stdClass;
        // 使用 instance 注册 foo 服务到 $bound 对象
        $resolved = $container->instance('foo', $bound);

        // 解析的结果和绑定的对象是同一个
        $this->assertSame($bound, $resolved);
    }

    /**
     * 扩展实例绑定会被保存下来
     */
    public function testExtendInstancesArePreserved()
    {
        $container = new Container;
        // 使用 bind 注册 foo 服务到闭包
        $container->bind('foo', function () {
            $obj = new stdClass;
            $obj->foo = 'bar';

            return $obj;
        });
        $obj = new stdClass;
        $obj->foo = 'foo';
        // 使用 instance 注册 foo 服务到 $obj 对象
        // 这样会覆盖之前的 bind 注册
        $container->instance('foo', $obj);

        // 使用 extend 扩展 foo 服务
        $container->extend('foo', function ($obj, $container) {
            $obj->bar = 'baz';

            return $obj;
        });

        // 再次使用 extend 扩展 foo 服务
        $container->extend('foo', function ($obj, $container) {
            $obj->baz = 'foo';

            return $obj;
        });

        // 通过 instance 注册的服务支持多次扩展，解析后的结果与预期一致
        $this->assertEquals('foo', $container->make('foo')->foo);
        $this->assertEquals('baz', $container->make('foo')->bar);
        $this->assertEquals('foo', $container->make('foo')->baz);
    }

    /**
     * 扩展是懒加载的
     */
    public function testExtendIsLazyInitialized()
    {
        ContainerLazyExtendStub::$initialized = false;

        $container = new Container;
        // 使用 bind 注册 Illuminate\Tests\Container\ContainerLazyExtendStub 服务
        $container->bind('Illuminate\Tests\Container\ContainerLazyExtendStub');
        // 使用 extend 扩展 Illuminate\Tests\Container\ContainerLazyExtendStub 服务，将解析出的对象执行 init() 操作并返回
        $container->extend('Illuminate\Tests\Container\ContainerLazyExtendStub', function ($obj, $container) {
            $obj->init();

            return $obj;
        });
        // 使用 make 解析之前，Illuminate\Tests\Container\ContainerLazyExtendStub 的 静态变量 $initialized 是 false
        $this->assertFalse(ContainerLazyExtendStub::$initialized);
        $container->make('Illuminate\Tests\Container\ContainerLazyExtendStub');
        // 使用 make 解析之后，Illuminate\Tests\Container\ContainerLazyExtendStub 的 静态变量 $initialized 是 true
        $this->assertTrue(ContainerLazyExtendStub::$initialized);
    }

    /**
     * 扩展可以在 bind 方法之前调用
     */
    public function testExtendCanBeCalledBeforeBind()
    {
        $container = new Container;
        // 使用 extend 扩展 foo 服务
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });
        // 使用数组方式注册 foo 服务
        $container['foo'] = 'foo';

        // 使用 make 解析 foo 服务的结果是扩展后的字符串
        $this->assertEquals('foobar', $container->make('foo'));
    }

    /**
     * 扩展实例服务会触发 rebinding 回调
     */
    public function testExtendInstanceRebindingCallback()
    {
        $_SERVER['_test_rebind'] = false;

        $container = new Container;
        // 使用 rebinding 注册 foo 服务的重新绑定回调，将 $_SERVER['_test_rebind'] 置为 true
        $container->rebinding('foo', function () {
            $_SERVER['_test_rebind'] = true;
        });

        $obj = new stdClass;
        // 使用 instance 注册 foo 服务
        $container->instance('foo', $obj);

        // 添加 extend 之前判断，这时候 $_SERVER['_test_rebind'] 未变化
        $this->assertFalse($_SERVER['_test_rebind']);

        // 使用 extend 扩展 foo 服务，会触发重新绑定操作
        $container->extend('foo', function ($obj, $container) {
            return $obj;
        });

        // $_SERVER['_test_rebind'] 发生改变
        $this->assertTrue($_SERVER['_test_rebind']);
    }

    /**
     * 扩展绑定服务会触发 rebinding 回调
     */
    public function testExtendBindRebindingCallback()
    {
        $_SERVER['_test_rebind'] = false;

        $container = new Container;
        // 使用 rebinding 注册 foo 服务的重新绑定回调，将 $_SERVER['_test_rebind'] 置为 true
        $container->rebinding('foo', function () {
            $_SERVER['_test_rebind'] = true;
        });

        // 使用 bind 注册 foo 服务
        $container->bind('foo', function () {
            return new stdClass;
        });

        // 这时候 $_SERVER['_test_rebind'] 未变化
        $this->assertFalse($_SERVER['_test_rebind']);

        // 使用 make 解析 foo 服务
        $container->make('foo');

        // 使用 extend 扩展 foo 服务，会触发重新绑定操作
        $container->extend('foo', function ($obj, $container) {
            return $obj;
        });

        // $_SERVER['_test_rebind'] 发生改变
        $this->assertTrue($_SERVER['_test_rebind']);
    }

    /**
     * 取消扩展
     */
    public function testUnsetExtend()
    {
        $container = new Container;
        // 使用 bind 注册 foo 服务到闭包，闭包返回标准类对象，包含 foo 属性
        $container->bind('foo', function () {
            $obj = new stdClass;
            $obj->foo = 'bar';

            return $obj;
        });

        // 使用 extend 扩展 foo 服务，给对象添加 bar 属性
        $container->extend('foo', function ($obj, $container) {
            $obj->bar = 'baz';

            return $obj;
        });

        // 使用 make 解析 foo 服务的结果包含了 foo 属性和 bar 属性，且值也与预期一致
        $class = $container->make('foo');
        $this->assertEquals('bar', $class->foo);
        $this->assertEquals('baz', $class->bar);

        // 取消 foo 服务的扩展
        $container->forgetExtenders('foo');

        // 重新使用 make 解析 foo 服务的结果只包含 foo 属性，且值也与预期一致
        // 不包含 bar 属性
        $class = $container->make('foo');
        $this->assertEquals('bar', $class->foo);
        $this->assertObjectNotHasAttribute('bar', $class);
    }

    /**
     * 默认参数解析
     */
    public function testResolutionOfDefaultParameters()
    {
        $container = new Container;
        // 使用 make 解析 Illuminate\Tests\Container\ContainerDefaultValueStub 服务，结果为 $instance
        $instance = $container->make('Illuminate\Tests\Container\ContainerDefaultValueStub');
        // $instance->stub 对象是 Illuminate\Tests\Container\ContainerConcreteStub 类的实例
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $instance->stub);
        // $instance->default 值是构造函数中的默认值
        $this->assertEquals('taylor', $instance->default);
    }

    public function testResolvingCallbacksAreCalledForSpecificAbstracts()
    {
        $container = new Container;
        $container->resolving('foo', function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testResolvingCallbacksAreCalled()
    {
        $container = new Container;
        $container->resolving(function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testResolvingCallbacksAreCalledForType()
    {
        $container = new Container;
        $container->resolving('stdClass', function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testUnsetRemoveBoundInstances()
    {
        $container = new Container;
        $container->instance('object', new stdClass);
        unset($container['object']);

        $this->assertFalse($container->bound('object'));
    }

    public function testBoundInstanceAndAliasCheckViaArrayAccess()
    {
        $container = new Container;
        $container->instance('object', new stdClass);
        $container->alias('object', 'alias');

        $this->assertTrue(isset($container['object']));
        $this->assertTrue(isset($container['alias']));
    }

    public function testReboundListeners()
    {
        unset($_SERVER['__test.rebind']);

        $container = new Container;
        $container->bind('foo', function () {
        });
        $container->rebinding('foo', function () {
            $_SERVER['__test.rebind'] = true;
        });
        $container->bind('foo', function () {
        });

        $this->assertTrue($_SERVER['__test.rebind']);
    }

    public function testReboundListenersOnInstances()
    {
        unset($_SERVER['__test.rebind']);

        $container = new Container;
        $container->instance('foo', function () {
        });
        $container->rebinding('foo', function () {
            $_SERVER['__test.rebind'] = true;
        });
        $container->instance('foo', function () {
        });

        $this->assertTrue($_SERVER['__test.rebind']);
    }

    public function testReboundListenersOnInstancesOnlyFiresIfWasAlreadyBound()
    {
        $_SERVER['__test.rebind'] = false;

        $container = new Container;
        $container->rebinding('foo', function () {
            $_SERVER['__test.rebind'] = true;
        });
        $container->instance('foo', function () {
        });

        $this->assertFalse($_SERVER['__test.rebind']);
    }

    /**
     * @expectedException \Illuminate\Contracts\Container\BindingResolutionException
     * @expectedExceptionMessage Unresolvable dependency resolving [Parameter #0 [ <required> $first ]] in class Illuminate\Tests\Container\ContainerMixedPrimitiveStub
     */
    public function testInternalClassWithDefaultParameters()
    {
        $container = new Container;
        $container->make('Illuminate\Tests\Container\ContainerMixedPrimitiveStub', []);
    }

    /**
     * @expectedException \Illuminate\Contracts\Container\BindingResolutionException
     * @expectedExceptionMessage Target [Illuminate\Tests\Container\IContainerContractStub] is not instantiable.
     */
    public function testBindingResolutionExceptionMessage()
    {
        $container = new Container;
        $container->make('Illuminate\Tests\Container\IContainerContractStub', []);
    }

    /**
     * @expectedException \Illuminate\Contracts\Container\BindingResolutionException
     * @expectedExceptionMessage Target [Illuminate\Tests\Container\IContainerContractStub] is not instantiable while building [Illuminate\Tests\Container\ContainerTestContextInjectOne].
     */
    public function testBindingResolutionExceptionMessageIncludesBuildStack()
    {
        $container = new Container;
        $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne', []);
    }

    public function testCallWithDependencies()
    {
        $container = new Container;
        $result = $container->call(function (stdClass $foo, $bar = []) {
            return func_get_args();
        });

        $this->assertInstanceOf('stdClass', $result[0]);
        $this->assertEquals([], $result[1]);

        $result = $container->call(function (stdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'taylor']);

        $this->assertInstanceOf('stdClass', $result[0]);
        $this->assertEquals('taylor', $result[1]);

        $stub = new ContainerConcreteStub;
        $result = $container->call(function (stdClass $foo, ContainerConcreteStub $bar) {
            return func_get_args();
        }, [ContainerConcreteStub::class => $stub]);

        $this->assertInstanceOf('stdClass', $result[0]);
        $this->assertSame($stub, $result[1]);

        /*
         * Wrap a function...
         */
        $result = $container->wrap(function (stdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'taylor']);

        $this->assertInstanceOf('Closure', $result);
        $result = $result();

        $this->assertInstanceOf('stdClass', $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    /**
     * @expectedException \ReflectionException
     * @expectedExceptionMessage Function ContainerTestCallStub() does not exist
     */
    public function testCallWithAtSignBasedClassReferencesWithoutMethodThrowsException()
    {
        $container = new Container;
        $result = $container->call('ContainerTestCallStub');
    }

    public function testCallWithAtSignBasedClassReferences()
    {
        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerTestCallStub@work', ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerTestCallStub@inject');
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $result[0]);
        $this->assertEquals('taylor', $result[1]);

        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerTestCallStub@inject', ['default' => 'foo']);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $result[0]);
        $this->assertEquals('foo', $result[1]);

        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerTestCallStub', ['foo', 'bar'], 'work');
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testCallWithCallableArray()
    {
        $container = new Container;
        $stub = new ContainerTestCallStub;
        $result = $container->call([$stub, 'work'], ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testCallWithStaticMethodNameString()
    {
        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerStaticMethodStub::inject');
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    public function testCallWithGlobalMethodName()
    {
        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\containerTestInject');
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    public function testCallWithBoundMethod()
    {
        $container = new Container;
        $container->bindMethod('Illuminate\Tests\Container\ContainerTestCallStub@unresolvable', function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call('Illuminate\Tests\Container\ContainerTestCallStub@unresolvable');
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $container->bindMethod('Illuminate\Tests\Container\ContainerTestCallStub@unresolvable', function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call([new ContainerTestCallStub, 'unresolvable']);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testBindMethodAcceptsAnArray()
    {
        $container = new Container;
        $container->bindMethod([\Illuminate\Tests\Container\ContainerTestCallStub::class, 'unresolvable'], function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call('Illuminate\Tests\Container\ContainerTestCallStub@unresolvable');
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $container->bindMethod([\Illuminate\Tests\Container\ContainerTestCallStub::class, 'unresolvable'], function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call([new ContainerTestCallStub, 'unresolvable']);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testContainerCanInjectDifferentImplementationsDependingOnContext()
    {
        $container = new Container;

        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStub');
        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectTwo')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $one = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $two = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectTwo');

        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $one->impl);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStubTwo', $two->impl);

        /*
         * Test With Closures
         */
        $container = new Container;

        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStub');
        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectTwo')->needs('Illuminate\Tests\Container\IContainerContractStub')->give(function ($container) {
            return $container->make('Illuminate\Tests\Container\ContainerImplementationStubTwo');
        });

        $one = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $two = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectTwo');

        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $one->impl);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStubTwo', $two->impl);
    }

    public function testContextualBindingWorksForExistingInstancedBindings()
    {
        $container = new Container;

        $container->instance('Illuminate\Tests\Container\IContainerContractStub', new ContainerImplementationStub);

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextualBindingWorksForNewlyInstancedBindings()
    {
        $container = new Container;

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $container->instance('Illuminate\Tests\Container\IContainerContractStub', new ContainerImplementationStub);

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextualBindingWorksOnExistingAliasedInstances()
    {
        $container = new Container;

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', 'Illuminate\Tests\Container\IContainerContractStub');

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedInstances()
    {
        $container = new Container;

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', 'Illuminate\Tests\Container\IContainerContractStub');

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedBindings()
    {
        $container = new Container;

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $container->bind('stub', ContainerImplementationStub::class);
        $container->alias('stub', 'Illuminate\Tests\Container\IContainerContractStub');

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextualBindingDoesntOverrideNonContextualResolution()
    {
        $container = new Container;

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', 'Illuminate\Tests\Container\IContainerContractStub');

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectTwo')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectTwo')->impl
        );

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStub',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextuallyBoundInstancesAreNotUnnecessarilyRecreated()
    {
        ContainerTestContextInjectInstantiations::$instantiations = 0;

        $container = new Container;

        $container->instance('Illuminate\Tests\Container\IContainerContractStub', new ContainerImplementationStub);
        $container->instance('Illuminate\Tests\Container\ContainerTestContextInjectInstantiations', new ContainerTestContextInjectInstantiations);

        $this->assertEquals(1, ContainerTestContextInjectInstantiations::$instantiations);

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerTestContextInjectInstantiations');

        $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');

        $this->assertEquals(1, ContainerTestContextInjectInstantiations::$instantiations);
    }

    public function testContainerTags()
    {
        $container = new Container;
        $container->tag('Illuminate\Tests\Container\ContainerImplementationStub', 'foo', 'bar');
        $container->tag('Illuminate\Tests\Container\ContainerImplementationStubTwo', ['foo']);

        $this->assertCount(1, $container->tagged('bar'));
        $this->assertCount(2, $container->tagged('foo'));
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $container->tagged('foo')[0]);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $container->tagged('bar')[0]);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStubTwo', $container->tagged('foo')[1]);

        $container = new Container;
        $container->tag(['Illuminate\Tests\Container\ContainerImplementationStub', 'Illuminate\Tests\Container\ContainerImplementationStubTwo'], ['foo']);
        $this->assertCount(2, $container->tagged('foo'));
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $container->tagged('foo')[0]);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStubTwo', $container->tagged('foo')[1]);

        $this->assertEmpty($container->tagged('this_tag_does_not_exist'));
    }

    public function testForgetInstanceForgetsInstance()
    {
        $container = new Container;
        $containerConcreteStub = new ContainerConcreteStub;
        $container->instance('Illuminate\Tests\Container\ContainerConcreteStub', $containerConcreteStub);
        $this->assertTrue($container->isShared('Illuminate\Tests\Container\ContainerConcreteStub'));
        $container->forgetInstance('Illuminate\Tests\Container\ContainerConcreteStub');
        $this->assertFalse($container->isShared('Illuminate\Tests\Container\ContainerConcreteStub'));
    }

    public function testForgetInstancesForgetsAllInstances()
    {
        $container = new Container;
        $containerConcreteStub1 = new ContainerConcreteStub;
        $containerConcreteStub2 = new ContainerConcreteStub;
        $containerConcreteStub3 = new ContainerConcreteStub;
        $container->instance('Instance1', $containerConcreteStub1);
        $container->instance('Instance2', $containerConcreteStub2);
        $container->instance('Instance3', $containerConcreteStub3);
        $this->assertTrue($container->isShared('Instance1'));
        $this->assertTrue($container->isShared('Instance2'));
        $this->assertTrue($container->isShared('Instance3'));
        $container->forgetInstances();
        $this->assertFalse($container->isShared('Instance1'));
        $this->assertFalse($container->isShared('Instance2'));
        $this->assertFalse($container->isShared('Instance3'));
    }

    public function testContainerFlushFlushesAllBindingsAliasesAndResolvedInstances()
    {
        $container = new Container;
        $container->bind('ConcreteStub', function () {
            return new ContainerConcreteStub;
        }, true);
        $container->alias('ConcreteStub', 'ContainerConcreteStub');
        $concreteStubInstance = $container->make('ConcreteStub');
        $this->assertTrue($container->resolved('ConcreteStub'));
        $this->assertTrue($container->isAlias('ContainerConcreteStub'));
        $this->assertArrayHasKey('ConcreteStub', $container->getBindings());
        $this->assertTrue($container->isShared('ConcreteStub'));
        $container->flush();
        $this->assertFalse($container->resolved('ConcreteStub'));
        $this->assertFalse($container->isAlias('ContainerConcreteStub'));
        $this->assertEmpty($container->getBindings());
        $this->assertFalse($container->isShared('ConcreteStub'));
    }

    public function testResolvedResolvesAliasToBindingNameBeforeChecking()
    {
        $container = new Container;
        $container->bind('ConcreteStub', function () {
            return new ContainerConcreteStub;
        }, true);
        $container->alias('ConcreteStub', 'foo');

        $this->assertFalse($container->resolved('ConcreteStub'));
        $this->assertFalse($container->resolved('foo'));

        $concreteStubInstance = $container->make('ConcreteStub');

        $this->assertTrue($container->resolved('ConcreteStub'));
        $this->assertTrue($container->resolved('foo'));
    }

    public function testGetAlias()
    {
        $container = new Container;
        $container->alias('ConcreteStub', 'foo');
        $this->assertEquals($container->getAlias('foo'), 'ConcreteStub');
    }

    public function testItThrowsExceptionWhenAbstractIsSameAsAlias()
    {
        $container = new Container;
        $container->alias('name', 'name');

        $this->expectException('LogicException');
        $this->expectExceptionMessage('[name] is aliased to itself.');

        $container->getAlias('name');
    }

    public function testContainerCanInjectSimpleVariable()
    {
        $container = new Container;
        $container->when('Illuminate\Tests\Container\ContainerInjectVariableStub')->needs('$something')->give(100);
        $instance = $container->make('Illuminate\Tests\Container\ContainerInjectVariableStub');
        $this->assertEquals(100, $instance->something);

        $container = new Container;
        $container->when('Illuminate\Tests\Container\ContainerInjectVariableStub')->needs('$something')->give(function ($container) {
            return $container->make('Illuminate\Tests\Container\ContainerConcreteStub');
        });
        $instance = $container->make('Illuminate\Tests\Container\ContainerInjectVariableStub');
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $instance->something);
    }

    public function testContainerGetFactory()
    {
        $container = new Container;
        $container->bind('name', function () {
            return 'Taylor';
        });

        $factory = $container->factory('name');
        $this->assertEquals($container->make('name'), $factory());
    }

    public function testExtensionWorksOnAliasedBindings()
    {
        $container = new Container;
        $container->singleton('something', function () {
            return 'some value';
        });
        $container->alias('something', 'something-alias');
        $container->extend('something-alias', function ($value) {
            return $value.' extended';
        });

        $this->assertEquals('some value extended', $container->make('something'));
    }

    public function testContextualBindingWorksWithAliasedTargets()
    {
        $container = new Container;

        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
        $container->alias('Illuminate\Tests\Container\IContainerContractStub', 'interface-stub');

        $container->alias('Illuminate\Tests\Container\ContainerImplementationStub', 'stub-1');

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('interface-stub')->give('stub-1');
        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectTwo')->needs('interface-stub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $one = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $two = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectTwo');

        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $one->impl);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStubTwo', $two->impl);
    }

    public function testResolvingCallbacksShouldBeFiredWhenCalledWithAliases()
    {
        $container = new Container;
        $container->alias('stdClass', 'std');
        $container->resolving('std', function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testMakeWithMethodIsAnAliasForMakeMethod()
    {
        $mock = $this->getMockBuilder(Container::class)
                     ->setMethods(['make'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('make')
             ->with(ContainerDefaultValueStub::class, ['default' => 'laurence'])
             ->will($this->returnValue(new stdClass));

        $result = $mock->makeWith(ContainerDefaultValueStub::class, ['default' => 'laurence']);

        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testResolvingWithArrayOfParameters()
    {
        $container = new Container;
        $instance = $container->make(ContainerDefaultValueStub::class, ['default' => 'adam']);
        $this->assertEquals('adam', $instance->default);

        $instance = $container->make(ContainerDefaultValueStub::class);
        $this->assertEquals('taylor', $instance->default);

        $container->bind('foo', function ($app, $config) {
            return $config;
        });

        $this->assertEquals([1, 2, 3], $container->make('foo', [1, 2, 3]));
    }

    public function testResolvingWithUsingAnInterface()
    {
        $container = new Container;
        $container->bind(IContainerContractStub::class, ContainerInjectVariableStubWithInterfaceImplementation::class);
        $instance = $container->make(IContainerContractStub::class, ['something' => 'laurence']);
        $this->assertEquals('laurence', $instance->something);
    }

    public function testNestedParameterOverride()
    {
        $container = new Container;
        $container->bind('foo', function ($app, $config) {
            return $app->make('bar', ['name' => 'Taylor']);
        });
        $container->bind('bar', function ($app, $config) {
            return $config;
        });

        $this->assertEquals(['name' => 'Taylor'], $container->make('foo', ['something']));
    }

    public function testNestedParametersAreResetForFreshMake()
    {
        $container = new Container;

        $container->bind('foo', function ($app, $config) {
            return $app->make('bar');
        });

        $container->bind('bar', function ($app, $config) {
            return $config;
        });

        $this->assertEquals([], $container->make('foo', ['something']));
    }

    public function testSingletonBindingsNotRespectedWithMakeParameters()
    {
        $container = new Container;

        $container->singleton('foo', function ($app, $config) {
            return $config;
        });

        $this->assertEquals(['name' => 'taylor'], $container->make('foo', ['name' => 'taylor']));
        $this->assertEquals(['name' => 'abigail'], $container->make('foo', ['name' => 'abigail']));
    }

    public function testCanBuildWithoutParameterStackWithNoConstructors()
    {
        $container = new Container;
        $this->assertInstanceOf(ContainerConcreteStub::class, $container->build(ContainerConcreteStub::class));
    }

    public function testCanBuildWithoutParameterStackWithConstructors()
    {
        $container = new Container;
        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
        $this->assertInstanceOf(ContainerDependentStub::class, $container->build(ContainerDependentStub::class));
    }

    public function testContainerKnowsEntry()
    {
        $container = new Container;
        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
        $this->assertTrue($container->has('Illuminate\Tests\Container\IContainerContractStub'));
    }

    public function testContainerCanBindAnyWord()
    {
        $container = new Container;
        $container->bind('Taylor', stdClass::class);
        $this->assertInstanceOf(stdClass::class, $container->get('Taylor'));
    }

    public function testContainerCanDynamicallySetService()
    {
        $container = new Container;
        $this->assertFalse(isset($container['name']));
        $container['name'] = 'Taylor';
        $this->assertTrue(isset($container['name']));
        $this->assertSame('Taylor', $container['name']);
    }

    /**
     * @expectedException \Illuminate\Container\EntryNotFoundException
     */
    public function testUnknownEntryThrowsException()
    {
        $container = new Container;
        $container->get('Taylor');
    }
}

class ContainerConcreteStub
{
}

interface IContainerContractStub
{
}

class ContainerImplementationStub implements IContainerContractStub
{
}

class ContainerImplementationStubTwo implements IContainerContractStub
{
}

class ContainerDependentStub
{
    public $impl;

    public function __construct(IContainerContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerNestedDependentStub
{
    public $inner;

    public function __construct(ContainerDependentStub $inner)
    {
        $this->inner = $inner;
    }
}

class ContainerDefaultValueStub
{
    public $stub;
    public $default;

    public function __construct(ContainerConcreteStub $stub, $default = 'taylor')
    {
        $this->stub = $stub;
        $this->default = $default;
    }
}

class ContainerMixedPrimitiveStub
{
    public $first;
    public $last;
    public $stub;

    public function __construct($first, ContainerConcreteStub $stub, $last)
    {
        $this->stub = $stub;
        $this->last = $last;
        $this->first = $first;
    }
}

class ContainerConstructorParameterLoggingStub
{
    public $receivedParameters;

    public function __construct($first, $second)
    {
        $this->receivedParameters = func_get_args();
    }
}

class ContainerLazyExtendStub
{
    public static $initialized = false;

    public function init()
    {
        static::$initialized = true;
    }
}

class ContainerTestCallStub
{
    public function work()
    {
        return func_get_args();
    }

    public function inject(ContainerConcreteStub $stub, $default = 'taylor')
    {
        return func_get_args();
    }

    public function unresolvable($foo, $bar)
    {
        return func_get_args();
    }
}

class ContainerTestContextInjectOne
{
    public $impl;

    public function __construct(IContainerContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerTestContextInjectTwo
{
    public $impl;

    public function __construct(IContainerContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerStaticMethodStub
{
    public static function inject(ContainerConcreteStub $stub, $default = 'taylor')
    {
        return func_get_args();
    }
}

class ContainerInjectVariableStub
{
    public $something;

    public function __construct(ContainerConcreteStub $concrete, $something)
    {
        $this->something = $something;
    }
}

class ContainerInjectVariableStubWithInterfaceImplementation implements IContainerContractStub
{
    public $something;

    public function __construct(ContainerConcreteStub $concrete, $something)
    {
        $this->something = $something;
    }
}

function containerTestInject(ContainerConcreteStub $stub, $default = 'taylor')
{
    return func_get_args();
}

class ContainerTestContextInjectInstantiations implements IContainerContractStub
{
    public static $instantiations;

    public function __construct()
    {
        static::$instantiations++;
    }
}
