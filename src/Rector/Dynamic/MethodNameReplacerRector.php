<?php declare(strict_types=1);

namespace Rector\Rector\Dynamic;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use Rector\Node\Attribute;
use Rector\NodeAnalyzer\MethodCallAnalyzer;
use Rector\NodeAnalyzer\MethodNameAnalyzer;
use Rector\NodeAnalyzer\StaticMethodCallAnalyzer;
use Rector\NodeChanger\MethodNameChanger;
use Rector\NodeChanger\StaticCallNameChanger;
use Rector\Rector\AbstractRector;

final class MethodNameReplacerRector extends AbstractRector
{
    /**
     * class => [
     *     oldMethod => newMethod
     * ]
     *
     * or (typically for static calls):
     *
     * class => [
     *     oldMethod => [
     *          newClass, newMethod
     *     ]
     * ]
     *
     * @todo consider splitting to static call replacer or class rename,
     * this api can lead users to bugs (already did)
     *
     * @var string[][]
     */
    private $perClassOldToNewMethods = [];

    /**
     * @var string[]
     */
    private $activeTypes = [];

    /**
     * @var MethodCallAnalyzer
     */
    private $methodCallAnalyzer;

    /**
     * @var StaticMethodCallAnalyzer
     */
    private $staticMethodCallAnalyzer;

    /**
     * @var MethodNameAnalyzer
     */
    private $methodNameAnalyzer;

    /**
     * @var MethodNameChanger
     */
    private $methodNameChanger;

    /**
     * @var StaticCallNameChanger
     */
    private $staticCallNameChanger;

    /**
     * @param string[][] $perClassOldToNewMethods
     */
    public function __construct(
        array $perClassOldToNewMethods,
        MethodCallAnalyzer $methodCallAnalyzer,
        StaticMethodCallAnalyzer $staticMethodCallAnalyzer,
        MethodNameAnalyzer $methodNameAnalyzer,
        StaticCallNameChanger $staticCallNameChanger,
        MethodNameChanger $methodNameChanger
    ) {
        $this->perClassOldToNewMethods = $perClassOldToNewMethods;
        $this->methodCallAnalyzer = $methodCallAnalyzer;
        $this->staticMethodCallAnalyzer = $staticMethodCallAnalyzer;
        $this->methodNameAnalyzer = $methodNameAnalyzer;
        $this->staticCallNameChanger = $staticCallNameChanger;
        $this->methodNameChanger = $methodNameChanger;
    }

    public function isCandidate(Node $node): bool
    {
        $this->activeTypes = [];

        $matchedTypes = $this->methodCallAnalyzer->matchTypes($node, $this->getClasses());
        if ($matchedTypes) {
            $this->activeTypes = $matchedTypes;

            return true;
        }

        $matchedTypes = $this->staticMethodCallAnalyzer->matchTypes($node, $this->getClasses());
        if ($matchedTypes) {
            $this->activeTypes = $matchedTypes;

            return true;
        }

        if ($this->isMethodName($node, $this->getClasses())) {
            return true;
        }

        return false;
    }

    /**
     * @param Identifier|StaticCall|MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Identifier) {
            return $this->resolveIdentifier($node);
        }

        $oldToNewMethods = $this->matchOldToNewMethods();

        /** @var Identifier $identifierNode */
        $identifierNode = $node->name;

        $methodName = $identifierNode->toString();
        if (! isset($oldToNewMethods[$methodName])) {
            return $node;
        }

        if ($node instanceof StaticCall && $this->isClassRename($oldToNewMethods)) {
            return $this->resolveClassRename($node, $oldToNewMethods, $methodName);
        }

        $this->methodNameChanger->renameNode($node, $oldToNewMethods[$methodName]);

        return $node;
    }

    /**
     * @return string[]
     */
    private function getClasses(): array
    {
        return array_keys($this->perClassOldToNewMethods);
    }

    /**
     * @param mixed[] $oldToNewMethods
     */
    private function isClassRename(array $oldToNewMethods): bool
    {
        $firstMethodConfiguration = current($oldToNewMethods);

        return is_array($firstMethodConfiguration);
    }

    /**
     * @return string[]
     */
    private function matchOldToNewMethods(): array
    {
        foreach ($this->activeTypes as $activeType) {
            if ($this->perClassOldToNewMethods[$activeType]) {
                return $this->perClassOldToNewMethods[$activeType];
            }
        }

        return [];
    }

    /**
     * @param string[] $types
     */
    private function isMethodName(Node $node, array $types): bool
    {
        if (! $this->methodNameAnalyzer->isOverrideOfTypes($node, $types)) {
            return false;
        }

        /** @var Identifier $node */
        $parentClassName = $node->getAttribute(Attribute::PARENT_CLASS_NAME);

        /** @var Identifier $node */
        if (! isset($this->perClassOldToNewMethods[$parentClassName][$node->toString()])) {
            return false;
        }

        $this->activeTypes = [$parentClassName];

        return true;
    }

    private function resolveIdentifier(Identifier $node): Node
    {
        $oldToNewMethods = $this->matchOldToNewMethods();

        $methodName = $node->name;
        if (! isset($oldToNewMethods[$methodName])) {
            return $node;
        }

        $node->name = $oldToNewMethods[$methodName];

        return $node;
    }

    /**
     * @param string[] $oldToNewMethods
     */
    private function resolveClassRename(StaticCall $staticCallNode, array $oldToNewMethods, string $methodName): Node
    {
        [$newClass, $newMethod] = $oldToNewMethods[$methodName];

        $staticCallNode->class = new Name($newClass);
        $this->staticCallNameChanger->renameNode($staticCallNode, $newMethod);

        return $staticCallNode;
    }
}
