<template>
  <router-view/>
</template>
<style>
body #app{
    font-size: 16px;
}
.bootstrap #app .form-control,
.bootstrap #app input[type=password],
.bootstrap #app input[type=search],
.bootstrap #app input[type=text],
.bootstrap #app select,
.bootstrap #app textarea {
  border: 0px !important;
  font-size: 16px !important;
}
.bootstrap #app .uneditable-input:hover, .bootstrap #app input[type=color]:hover, .bootstrap #app input[type=date]:hover, .bootstrap #app input[type=datetime-local]:hover, .bootstrap #app input[type=datetime]:hover, .bootstrap #app input[type=email]:hover, .bootstrap #app input[type=month]:hover, .bootstrap #app input[type=number]:hover, .bootstrap #app input[type=password]:focus, .bootstrap #app input[type=search]:hover, .bootstrap #app input[type=tel]:hover, .bootstrap #app input[type=text]:hover, .bootstrap #app input[type=time]:hover, .bootstrap #app input[type=url]:hover, .bootstrap #app input[type=week]:hover, .bootstrap #app textarea:hover {
    background-color: #fff;
}
.bootstrap #app label.v-label {
  display: flex;
}
</style>