package com.lostandfound;

public class LostItem extends Item {
    public LostItem(int id, String title, String description, String category, String location) {
        super(id, title, description, category, location, "lost");
    }
}
