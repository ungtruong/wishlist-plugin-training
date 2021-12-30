import template from './hello-world.html.twig';
const { Component } = Shopware;


Component.register('hello-world', {
    data() {
      
        // return {
        //   value: 10000,
        //   isChecked : this.defaultValue == 0 ? 'true' : 'true',
        // };
      console.log(this.defaultValue);
        this.isChecked = this.defaultValue <= 0 ? false : true;
        this.isDisabled = !this.isChecked ;
    },
    methods: {
      checkInput2: function (e) {
        console.log(e);
        if(e.target.checked){
          this.isDisabled = false;
          
        } else {
          this.isDisabled = true;
          this.defaultValue = 1;

        }
      },
      setInput: function (e) {
        this.defaultValue = e
      }
  },
    props: {
      defaultValue: {
          type: String,
          required: true,
      },
      fieldType: {
          type: String,
          required: false,
          default: 'text',
      },
      isDisabled: {
        type: String,
        required: false
      },
      isChecked: 
      {
        type: Boolean,
        required: false
      }

  },
  template: template
});