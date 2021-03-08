const {Component, Mixin} = Shopware;
import template from './clarobi-api-test-button.html.twig';

Component.register('clarobi-api-test-button', {
    template,

    props: ['label'],
    inject: ['clarobiApiTest'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
        };
    },

    computed: {
        pluginConfig() {
            let config = this.$parent.$parent.$parent.actualConfigData;
            if (config) {
                return config.null;
            }

            // in SW6.3.4 it's one step above
            return this.$parent.$parent.$parent.$parent.actualConfigData.null;
        }
    },

    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
        },

        check() {
            this.isLoading = true;
            this.clarobiApiTest.check(this.pluginConfig).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('clarobi-api-test-button.title'),
                        message: this.$tc('clarobi-api-test-button.success')
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('clarobi-api-test-button.title'),
                        message: this.$tc('clarobi-api-test-button.error')
                    });
                }

                // this.isLoading = false;
                setTimeout(() => {
                    this.isLoading = false;
                }, 2500);
            });
        }
    }
})
