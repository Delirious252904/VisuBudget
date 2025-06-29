<!-- -------------------------------------------------- -->
<!-- File: app/views/home/contact.php                     -->
<!-- -------------------------------------------------- -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-xl mx-auto">
    <div class="text-center">
        <h1 class="text-3xl font-bold">Get In Touch</h1>
        <p class="text-gray-400 mt-2">Have a question, feedback, or a partnership inquiry? We'd love to hear from you.</p>
    </div>

    <form action="/contact" method="POST" class="mt-8 space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-300">Your Name</label>
            <input type="text" name="name" id="name" class="form-input mt-1" required>
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-300">Your Email</label>
            <input type="email" name="email" id="email" class="form-input mt-1" required>
        </div>
        <div>
            <label for="message" class="block text-sm font-medium text-gray-300">Message</label>
            <textarea name="message" id="message" rows="5" class="form-input mt-1" required></textarea>
        </div>
        <div>
            <button type="submit" class="w-full btn btn-primary">Send Message</button>
        </div>
    </form>
</section>