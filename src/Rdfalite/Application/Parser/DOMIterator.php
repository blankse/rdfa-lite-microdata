<?php

/**
 * rdfa-lite
 *
 * @category Jkphl
 * @package Jkphl\Rdfalite
 * @subpackage Jkphl\Rdfalite\Application
 * @author Joschi Kuphal <joschi@tollwerk.de> / @jkphl
 * @copyright Copyright © 2017 Joschi Kuphal <joschi@tollwerk.de> / @jkphl
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */

/***********************************************************************************
 *  The MIT License (MIT)
 *
 *  Copyright © 2017 Joschi Kuphal <joschi@kuphal.net> / @jkphl
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of
 *  this software and associated documentation files (the "Software"), to deal in
 *  the Software without restriction, including without limitation the rights to
 *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 *  the Software, and to permit persons to whom the Software is furnished to do so,
 *  subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 ***********************************************************************************/

namespace Jkphl\Rdfalite\Application\Parser;

use Jkphl\Rdfalite\Application\Contract\ElementProcessorInterface;

/**
 * Recursive DOM node iterator
 *
 * @package Jkphl\Rdfalite
 * @subpackage Jkphl\Rdfalite\Application
 */
class DOMIterator extends \ArrayIterator implements \RecursiveIterator
{
    /**
     * Element processor
     *
     * @var ElementProcessorInterface
     */
    protected $elementProcessor;
    /**
     * Initial parser context
     *
     * @var Context
     */
    protected $initialContext;
    /**
     * Registered contexts
     *
     * @var Context[]
     */
    public $contexts = [];
    /**
     * Element context map
     *
     * @var array
     */
    protected $contextMap = [];

    /**
     * Recursive DOM node iterator constructor
     *
     * @param \DOMNodeList $nodeList Node list
     * @param Context $initialContext Initial parser context
     * @param ElementProcessorInterface $elementProcessor Element processor
     */
    public function __construct(
        \DOMNodeList $nodeList,
        Context $initialContext,
        ElementProcessorInterface $elementProcessor
    ) {
        $this->elementProcessor = $elementProcessor;
        $this->initialContext = $initialContext;

        $nodes = [];

        // Run through all nodes
        /** @var \DOMNode $node */
        foreach ($nodeList as $node) {
            $nodeId = spl_object_hash($node);

            // If it's an element node: Process it and register it's local context
            if ($node->nodeType == XML_ELEMENT_NODE) {
                /** @var \DOMElement $node */
                $localContext = $this->elementProcessor->processElement($node, $this->initialContext);

                // Register the node context
                $localContextId = spl_object_hash($localContext);
                if (empty($this->contexts[$localContextId])) {
                    $this->contexts[$localContextId] = $localContext;
                }

                $this->contextMap[$nodeId] = $localContextId;
            }

            // Register the node
            $nodes[$nodeId] = $node;
        }

        parent::__construct($nodes);
    }

    /**
     * Return the recursive iterator
     *
     * @return \RecursiveIteratorIterator Recursive iterator
     */
    public function getRecursiveIterator()
    {
        return new \RecursiveIteratorIterator($this, \RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * Return whether the current node has child nodes
     *
     * This method gets called once per element and prior to the call to current(),
     * so this seems like the perfect place for the first processing steps (even
     * for elements without children).
     *
     * @return boolean Current node has child nodes
     */
    public function hasChildren()
    {
        return $this->current()->hasChildNodes();
    }

    /**
     * Return a child node iterator
     *
     * @return DOMIterator Child node iterator
     */
    public function getChildren()
    {
        $element = $this->current();
        $childContext = $this->elementProcessor->processElementChildren(
            $element,
            $this->contexts[$this->contextMap[$this->key()]]
        );
        return new static($element->childNodes, $childContext, $this->elementProcessor);
    }

    /**
     * Rewind array back to the start
     *
     * @return void
     */
    public function rewind()
    {
        parent::rewind();
    }

    /**
     * Return the context in it's current state
     *
     * @return Context Context
     */
    public function getContext() {
        return $this->initialContext;
    }
}
