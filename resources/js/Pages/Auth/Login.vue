<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Input from "@/Components/UI/input/Input.vue";
import { Button } from "@/Components/UI/button/index.js";
import { Label } from "@/Components/UI/label/index.js";
import { Card } from "@/Components/UI/card/index.js";

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head>Log in · nghCRM-v2</Head>
  <div class="flex min-h-screen items-center justify-center bg-stone-800 px-4">
    <Card class="w-full max-w-sm p-8">
      <div class="mb-8 flex flex-col items-center gap-3">
        <svg class="h-8 w-auto fill-current text-primary" viewBox="0 0 20 20">
          <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z" />
        </svg>
        <h1 class="text-xl font-bold">ngh-CRMv2</h1>
        <p class="text-sm text-muted-foreground">Sign in to your account</p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="space-y-2">
          <Label for="email">Email</Label>
          <Input
              id="email"
              type="email"
              autocomplete="username"
              autofocus
              v-model="form.email"
          />
          <p
              v-if="form.errors.email"
              class="text-sm text-destructive"
          >
            {{ form.errors.email }}
          </p>
        </div>

        <div class="space-y-2">
          <Label for="password">Password</Label>
          <Input
              id="password"
              type="password"
              autocomplete="current-password"
              v-model="form.password"
          />
          <p
              v-if="form.errors.password"
              class="text-sm text-destructive"
          >
            {{ form.errors.password }}
          </p>
        </div>

        <label class="flex items-center gap-2 text-sm">
          <input
              type="checkbox"
              v-model="form.remember"
              class="rounded border-input"
          >
          <span>Remember me</span>
        </label>

        <Button
            type="submit"
            class="w-full"
            :disabled="form.processing"
        >Log in
        </Button>
      </form>
    </Card>
  </div>
</template>
