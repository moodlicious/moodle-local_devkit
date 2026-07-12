// Test only, remove file before merging PR.
/* eslint
  no-unused-vars: "error",
  eqeqeq: "error",
  curly: "error",
  no-var: "error",
  prefer-const: "error",
  no-extra-semi: "error",
  no-unreachable: "error",
  no-debugger: "error",
  no-console: "error",
  no-constant-condition: "error",
  no-self-compare: "error",
  no-unneeded-ternary: "error",
  yoda: "error",
  object-shorthand: "error",
  quote-props: "error",
  semi: ["error", "always"],
  quotes: ["error", "single"]
*/

var x = 1;
var y = 2;

let z = 3;

const unused = 123;

if (x == y) console.log("equal");

if (true) {
    debugger;
}

if (x === x) {
    console.log("self compare");
}

const obj = {
    a: 1,
    b: function () {
        return 1;
    },
};

const ternary = x ? true : false;

function foo() {
    return;
    console.log("unreachable");
}

const f = function () {
    return 42;
};

if (5 === x) {
    console.log("yoda");
}

export {};
